<?php

namespace Monteiro\TelescopeMailAttachments\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectJavaScript
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldInject($response)) {
            return $response;
        }

        $content = $response->getContent();

        $js = file_get_contents(__DIR__.'/../../../resources/js/telescope-mail-attachments.js');

        if ($js === false) {
            return $response;
        }

        $script = "<script type=\"module\">{$js}</script>";

        $content = str_replace('</body>', $script.'</body>', $content);

        $response->setContent($content);

        return $response;
    }

    /**
     * Determine if the JavaScript should be injected into the response.
     */
    protected function shouldInject(Response $response): bool
    {
        if (! method_exists($response, 'getContent')) { // @phpstan-ignore function.alreadyNarrowedType
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html');
    }
}
