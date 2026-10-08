<?php

declare(strict_types=1);

/*
 * Builds the read-only demo published at https://ruvelo.github.io/laravel-help-center/.
 *
 * Seeds the Halyard help center in an in-memory database, renders every
 * page through the package's real routes and views, and writes the HTML
 * out as static files. What would need the server (search suggestions, the
 * search page, "Was this helpful?") is answered in the browser by
 * demo/demo.js from an index of the articles.
 *
 *   php demo/build.php <site-dir> [<shots-dir>]
 *
 * <shots-dir>, when given, receives the pages the screenshots are taken of:
 * without the demo banner, and the signed-in editor.
 */

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Foundation\Application;
use Ruvelo\HelpCenter\HelpCenterServiceProvider;
use Ruvelo\HelpCenter\Models\Article;
use Ruvelo\HelpCenter\Models\Category;

require __DIR__.'/../vendor/autoload.php';

const HOST = 'https://ruvelo.github.io';
const PATH = 'laravel-help-center';
const REPO = 'https://github.com/Ruvelo/laravel-help-center';

$site = rtrim($argv[1] ?? __DIR__.'/../build/site', '/');
$shots = isset($argv[2]) ? rtrim($argv[2], '/') : null;

foreach ([
    'APP_ENV' => 'testing',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'APP_URL' => HOST,
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'SESSION_DRIVER' => 'array',
    'HELP_CENTER_PATH' => PATH,
    'HELP_CENTER_NAME' => 'Halyard Help Center',
    'HELP_CENTER_CONTACT_EMAIL' => 'support@halyard.test',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

class DemoUser extends User
{
    protected $table = 'users';

    protected $guarded = [];
}

$app = Application::create(basePath: null, options: ['extra' => ['providers' => [HelpCenterServiceProvider::class]]]);
$app['config']->set('auth.providers.users.model', DemoUser::class);
$app['config']->set('help-center.app', ['label' => 'Back to Halyard', 'url' => HOST.'/'.PATH.'/in-your-app/']);

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
$app->make(ConsoleKernel::class)->call('migrate', ['--force' => true]);

// --- Seed -------------------------------------------------------------------

$people = [];
foreach (['Maya Okafor', 'Tom Reyes', 'Inès Laurent', 'Kenji Mori'] as $name) {
    $first = strtok($name, ' ');
    $people[$first] = DemoUser::forceCreate([
        'name' => $name,
        'email' => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $first)).'@halyard.test',
        'password' => bin2hex(random_bytes(16)),
    ]);
}

// Maya and Inès run support; they edit the help center.
Gate::define('help-center-edit', fn (DemoUser $user) => in_array($user->email, ['maya@halyard.test', 'ines@halyard.test'], true));

$content = require __DIR__.'/content.php';
$now = Carbon::now();

Carbon::setTestNow($now->copy()->subDays(60));
foreach ($content['categories'] as $position => [$icon, $name, $description]) {
    (new Category)->commit(['name' => $name, 'icon' => $icon, 'description' => $description, 'position' => $position + 1]);
}

$positions = [];
foreach ($content['articles'] as [$category, $title, $days, $views, [$yes, $no], $body]) {
    Carbon::setTestNow($now->copy()->subDays($days)->subHours(3));
    $article = (new Article)->commit([
        'title' => $title,
        'body' => $body,
        'status' => Article::PUBLISHED,
        'category_id' => Category::query()->where('slug', $category)->value('id'),
        'position' => $positions[$category] = ($positions[$category] ?? 0) + 1,
    ]);

    foreach ($content['comments'] as [$slug, $comment]) {
        if ($slug === $article->slug) {
            Carbon::setTestNow($now->copy()->subHours(random_int(2, 90)));
            $article->recordFeedback('demo-'.$slug, false, $comment);
        }
    }

    // Set the totals directly: one row per vote would be noise here.
    $app['db']->table($article->getTable())->where('id', $article->id)
        ->update(['views' => $views, 'helpful_count' => $yes, 'not_helpful_count' => $no]);
}

foreach ($content['drafts'] as [$category, $title, $body]) {
    Carbon::setTestNow($now->copy()->subHours(5));
    (new Article)->commit([
        'title' => $title,
        'body' => $body,
        'status' => Article::DRAFT,
        'category_id' => Category::query()->where('slug', $category)->value('id'),
    ]);
}
Carbon::setTestNow();

// --- Render -----------------------------------------------------------------

$http = $app->make(HttpKernel::class);

$fetch = function (string $path, ?User $as = null) use ($app, $http): string {
    $app['auth']->forgetGuards();
    if ($as !== null) {
        $app['auth']->guard()->setUser($as);
    }

    $url = HOST.'/'.PATH.($path === '' ? '' : '/'.$path);
    $request = Request::create($url, server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 (demo build; not counted)', 'HTTP_SEC_PURPOSE' => 'prefetch']);
    $response = $http->handle($request);
    $http->terminate($request, $response);

    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException("GET {$url} answered {$response->getStatusCode()}");
    }

    // Root-relative links, so the same files work on Pages and on localhost.
    return str_replace(HOST.'/', '/', (string) $response->getContent());
};

$banner = '<div style="background:var(--help-accent);color:var(--help-on-accent);font:500 .875rem/1.4 var(--help-sans);padding:.55rem 16px;text-align:center">'
    .'You’re looking at a read-only demo of <a href="'.REPO.'" style="color:inherit;font-weight:700">ruvelo/laravel-help-center</a>. '
    .'Search and “Was this helpful?” work in your browser; install it to write articles. '
    .'<a href="/'.PATH.'/in-your-app/" style="color:inherit">See it inside an app</a> · '
    .'<a href="'.REPO.'#readme" style="color:inherit">Read the docs</a></div>';

$demoScript = '<script src="/'.PATH.'/assets/demo.js"></script>';

$write = function (string $root, string $path, string $html, bool $withBanner = true) use ($banner, $demoScript): void {
    if (str_contains($html, '</head>')) {
        $html = preg_replace('/<\/head>/', $demoScript.'</head>', $html, 1) ?? $html;
    }
    if ($withBanner) {
        $html = preg_replace('/<body( class="hc")?>/', '$0'.$banner, $html, 1) ?? $html;
    }
    $file = $root.'/'.($path === '' ? '' : rawurldecode($path).'/').'index.html';
    is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
    file_put_contents($file, $html);
};

$paths = ['', 'search'];
foreach (Category::query()->orderBy('position')->pluck('slug') as $slug) {
    $paths[] = 'categories/'.$slug;
}
$published = Article::query()->published()->with('category')->orderBy('id')->get();
foreach ($published as $article) {
    $paths[] = 'articles/'.$article->slug;
}

foreach ($paths as $path) {
    $write($site, $path, $fetch($path));
}

file_put_contents($site.'/sitemap.xml', $fetch('sitemap.xml'));

// A page from the (pretend) Halyard app, using the Blade component.
$app['auth']->forgetGuards();
$inApp = str_replace(HOST.'/', '/', $app['view']->file(__DIR__.'/app.blade.php')->render());
$write($site, 'in-your-app', str_replace('<body>', '<body style="--help-accent:#3d4eff;--help-on-accent:#fff;--help-sans:ui-sans-serif,system-ui,sans-serif">', $inApp));

// The browser-side search index and the script that uses it.
$index = $published->map(fn (Article $article) => [
    'title' => $article->title,
    'slug' => $article->slug,
    'url' => '/'.PATH.'/articles/'.rawurlencode($article->slug),
    'category' => $article->category?->name,
    'excerpt' => $article->summary(),
    'body' => $article->plainText(),
    'views' => $article->views,
])->values();

is_dir($site.'/assets') || mkdir($site.'/assets', 0777, true);
file_put_contents($site.'/assets/demo.js', 'window.HELP_BASE='.json_encode('/'.PATH.'/').';window.HELP_INDEX='
    .json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG).";\n"
    .file_get_contents(__DIR__.'/demo.js'));

if ($shots !== null) {
    // Type into the hero so the suggestions are open in the screenshot.
    $typing = '<script>addEventListener("load",()=>{const i=document.getElementById("hc-hero-search");i.focus();i.value="invoice";i.dispatchEvent(new Event("input"))})</script>';

    // Screenshot machines often lack an emoji font; real readers have one.
    // demo/screenshots.sh serves the font file next to the shots.
    $emoji = '<link rel="preload" href="/emoji.ttf" as="font" type="font/ttf" crossorigin><style>@font-face{font-family:"Noto Color Emoji";src:url(/emoji.ttf);font-display:block}</style>'
        // Lay the page out again once the font is in, so no glyph keeps its fallback.
        .'<script>document.fonts.load(\'1em "Noto Color Emoji"\',\'🚀🧾\').then(()=>{document.body.style.display="none";document.body.offsetHeight;document.body.style.display=""})</script>';
    $shot = fn (string $html) => str_replace('</head>', $emoji.'</head>', $html);

    foreach ([
        'home' => $fetch(''),
        'suggest' => str_replace('</body>', $typing.'</body>', $fetch('')),
        'article' => $fetch('articles/download-an-invoice'),
        'dark' => $fetch('articles/when-will-i-get-paid'),
        'search' => $fetch('search?q=refund&all=1'),
        'editor' => $fetch('manage/articles/use-the-halyard-api/edit', $people['Maya']),
        'manage' => $fetch('manage', $people['Maya']),
        'feedback' => $fetch('manage/feedback', $people['Maya']),
    ] as $name => $html) {
        $write($shots, $name, $shot($html), false);
    }
    $write($shots, 'widget', $shot($inApp), false);
}

fwrite(STDOUT, sprintf("Built %d demo pages (%d articles) into %s\n", count($paths) + 1, $published->count(), $site));
