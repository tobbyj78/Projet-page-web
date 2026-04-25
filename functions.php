<?php
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function getRoleRedirect(string $role): string {
    return match($role) {
        'admin'        => 'index_admin.php',
        'restaurateur' => 'index_restaurateur.php',
        'livreur'      => 'index_livreur.php',
        default        => 'index.php',
    };
}