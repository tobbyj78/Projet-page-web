<?php
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function catalogue_image(string $name, string $placeholder): string {
    $slug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $name));
    $abs  = __DIR__ . '/images/catalogue/' . $slug . '.webp';
    return file_exists($abs) ? '/images/catalogue/' . $slug . '.webp' : $placeholder;
}

function getRoleRedirect(string $role): string {
    return match($role) {
        'admin'        => 'index_admin.php',
        'restaurateur' => 'index_restaurateur.php',
        'livreur'      => 'index_livreur.php',
        default        => 'index.php',
    };
}