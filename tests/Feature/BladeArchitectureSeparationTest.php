<?php

use Symfony\Component\Finder\Finder;

test('all blade templates strictly enforce zero inline js and zero inline css separation', function () {
    $viewsPath = resource_path('views');
    $finder = (new Finder)->files()->in($viewsPath)->name('*.blade.php');

    $violations = [];

    $eventHandlers = ['click', 'change', 'submit', 'input', 'keydown', 'keyup', 'keypress', 'load', 'error', 'mouseover', 'mouseout'];
    $eventPattern = '/\bon(?:'.implode('|', $eventHandlers).')\s*=/i';

    foreach ($finder as $file) {
        $content = $file->getContents();
        $relative = $file->getRelativePathname();

        // 1. Prohibit inline <script> blocks
        if (preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $content)) {
            $violations[] = "{$relative}: Contains prohibited inline <script> block.";
        }

        // 2. Prohibit inline event handlers
        if (preg_match($eventPattern, $content, $matches)) {
            $violations[] = "{$relative}: Contains prohibited inline event handler ({$matches[0]}).";
        }

        // 3. Prohibit inline <style> blocks
        if (preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $content)) {
            $violations[] = "{$relative}: Contains prohibited inline <style> block.";
        }

        // 4. Prohibit style="..." attributes
        if (preg_match('/<[^>]*\bstyle\s*=\s*["\'][^"\']*["\'][^>]*>/is', $content, $matches)) {
            $violations[] = "{$relative}: Contains prohibited inline style attribute ({$matches[0]}).";
        }
    }

    expect($violations)->toBeEmpty(
        "Blade Separation Architecture Violations detected:\n".implode("\n", $violations)
    );
});
