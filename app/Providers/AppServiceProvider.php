<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->verificarAlmacenamiento();

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::transliterate(
                Str::lower($request->string('email')).'|'.$request->ip()
            ));
        });

        View::composer(['layouts.staff', 'layouts.admin'], function (ViewContract $view): void {
            $view->with('navigation', Auth::user()?->role->navigation() ?? []);
        });
    }

    /**
     * Fail early when production points the dish media at Supabase but the S3
     * credentials are incomplete.
     *
     * The alternative is a request that reaches the upload endpoint and only
     * then discovers a missing key, with the file already lost. The message
     * names variables, never their values.
     */
    private function verificarAlmacenamiento(): void
    {
        if (! $this->app->isProduction() || config('menuvision.disk') !== 'supabase') {
            return;
        }

        $disco = (array) config('filesystems.disks.supabase', []);

        $variables = [
            'AWS_ACCESS_KEY_ID' => 'key',
            'AWS_SECRET_ACCESS_KEY' => 'secret',
            'AWS_DEFAULT_REGION' => 'region',
            'AWS_BUCKET' => 'bucket',
            'AWS_ENDPOINT' => 'endpoint',
            'AWS_URL' => 'url',
        ];

        $faltantes = [];

        foreach ($variables as $variable => $clave) {
            if (blank($disco[$clave] ?? null)) {
                $faltantes[] = $variable;
            }
        }

        if ($faltantes !== []) {
            throw new RuntimeException(
                'Faltan variables de entorno para Supabase Storage: '.implode(', ', $faltantes).'.'
            );
        }

        if (! str_contains((string) config('filesystems.disks.supabase.url'), '/storage/v1/object/public/')) {
            throw new RuntimeException(
                'AWS_URL debe ser la URL pública del bucket: https://<proyecto>.supabase.co/storage/v1/object/public/<bucket>.'
            );
        }
    }
}
