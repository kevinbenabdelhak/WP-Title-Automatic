<?php
/**
 * Plugin Name: WP Title Automatic
 * Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-title-automatic/
 * Description: Génère automatiquement des titres pour les publications et les pages.
 * Version: 1.1
 * Author: Kevin Benabdelhak
 * Author URI: https://kevin-benabdelhak.fr/
 * Contributors: kevinbenabdelhak
 */

if (!defined('ABSPATH')) {
    exit; // Sortir si accès direct
}


// Chargement des fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'inc/options-page.php';
require_once plugin_dir_path(__FILE__) . 'inc/title-generation.php';

// Ajout des styles et scripts
add_action('admin_enqueue_scripts', 'WP_Title_Automatic_enqueue_admin_scripts');
function WP_Title_Automatic_enqueue_admin_scripts($hook) {
    // On inclut les scripts/CSS uniquement sur les pages d'administration nécessaires
    if ( 'edit.php' !== $hook && 'settings_page_wp_title_automatic' !== $hook ) {
        return;
    }

    wp_enqueue_style('wp_title_automatic_admin_style', plugin_dir_url(__FILE__) . 'css/admin-style.css');
    wp_enqueue_script('wp_title_automatic_admin_script', plugin_dir_url(__FILE__) . 'js/admin-script.js', ['jquery'], null, true);
    
    // Récupérer les options du plugin
    $options = get_option('wpta_settings');
    
    // Localiser le script avec les données nécessaires
    wp_localize_script('wp_title_automatic_admin_script', 'WPTitleAutomatic', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpta_nonce'),
        'settings' => [
            'wpta_prompt' => $options['wpta_prompt'] ?? '',
            'wpta_suggestions' => $options['wpta_suggestions'] ?? [],
            'wpta_consider_title' => $options['wpta_consider_title'] ?? 'existing'
        ],
    ]);
}
