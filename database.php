<?php

function getDatabaseConnection() {
    // Chemin direct vers ton fichier SQLite
    $dsn = "sqlite:database/data.db";

    try {
        // Création de la connexion
        $pdo = new PDO($dsn);
        
        // Optionnel mais recommandé : configurer PDO pour qu'il signale les erreurs SQL
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // On retourne l'objet pour s'en servir ailleurs
        return $pdo; 
        
    } catch (PDOException $e) {
        // En cas d'échec, on arrête le script et on affiche l'erreur
        error_log("Erreur de connexion DB : " . $e->getMessage());
        die("Erreur : Impossible de se connecter à la base de données.");
    }
}
