<?php


if (!defined('ABSPATH')) {
    exit; 
}

// Ajout de la page d'options dans le menu Admin
add_action('admin_menu', 'WP_Title_Automatic_add_admin_menu');
function WP_Title_Automatic_add_admin_menu() {
    add_options_page('WP Title Automatic', 'WP Title Automatic', 'manage_options', 'wp_title_automatic', 'WP_Title_Automatic_options_page');
}

// Enregistrement des paramètres pour la page d'options
add_action('admin_init', 'WP_Title_Automatic_settings_init');
function WP_Title_Automatic_settings_init() {
    register_setting('pluginPage', 'wpta_settings');

    add_settings_section(
        'wpta_pluginPage_section',
        __('Réglages du plugin pour générer des titres', 'wp-title-automatic'),
        null,
        'pluginPage'
    );

    add_settings_field(
        'wpta_api_key',
        __('Clé API gpt-4o-mini', 'wp-title-automatic'),
        'WP_Title_Automatic_api_key_render',
        'pluginPage',
        'wpta_pluginPage_section'
    );

    add_settings_field(
        'wpta_suggestions',
        __('Suggestions', 'wp-title-automatic'),
        'WP_Title_Automatic_suggestions_render',
        'pluginPage',
        'wpta_pluginPage_section'
    );

    add_settings_field(
        'wpta_consider_title',
        __('Prendre en compte le titre ou les premiers mots', 'wp-title-automatic'),
        'WP_Title_Automatic_consider_title_render',
        'pluginPage',
        'wpta_pluginPage_section'
    );

    add_settings_field(
        'wpta_prompt',
        __('Autres instructions pour l\'API', 'wp-title-automatic'),
        'WP_Title_Automatic_prompt_render',
        'pluginPage',
        'wpta_pluginPage_section'
    );
}

function WP_Title_Automatic_api_key_render() {
    $options = get_option('wpta_settings');
    ?>
    <input type='text' name='wpta_settings[wpta_api_key]' value='<?php echo esc_attr($options['wpta_api_key'] ?? ''); ?>'>
    <?php
}

function WP_Title_Automatic_prompt_render() {
    $options = get_option('wpta_settings');
    ?>
    <textarea name='wpta_settings[wpta_prompt]' rows='5' cols='50'><?php echo esc_textarea($options['wpta_prompt'] ?? ''); ?></textarea>
    <?php
}

function WP_Title_Automatic_suggestions_render() {
    $options = get_option('wpta_settings');
    $suggestions = $options['wpta_suggestions'] ?? [];
    $current_year = date('Y'); // Récupération de l'année actuelle
    $suggestion_options = [
        'Ajouter un verbe au début des titres',
        'Utiliser des adjectifs attractifs',
        'Faire des titres interrogatifs',
        'Inclure l’année ' . $current_year . ' dans le titre',
        'Utiliser des chiffres dans le titre'
    ];
    
    foreach ($suggestion_options as $suggestion) {
        $checked = in_array($suggestion, $suggestions) ? 'checked' : '';
        echo '<div><label><input type="checkbox" name="wpta_settings[wpta_suggestions][]" value="' . esc_attr($suggestion) . '" ' . $checked . '> ' . esc_html($suggestion) . '</label></div>';
    }
}

function WP_Title_Automatic_consider_title_render() {
    $options = get_option('wpta_settings');
    $consider_title = $options['wpta_consider_title'] ?? 'existing'; // Valeur par défaut

    $existing_checked = ($consider_title === 'existing') ? 'checked' : '';
    $first_300_checked = ($consider_title === 'first_300') ? 'checked' : '';

    echo '<label><input type="radio" name="wpta_settings[wpta_consider_title]" value="existing" ' . $existing_checked . '> ' . __('Prendre en compte le titre existant', 'wp-title-automatic') . '</label><br>';
    echo '<label><input type="radio" name="wpta_settings[wpta_consider_title]" value="first_300" ' . $first_300_checked . '> ' . __('Prendre en compte les 300 premiers mots', 'wp-title-automatic') . '</label>';
}

function WP_Title_Automatic_options_page() {
    ?>
    <form action='options.php' method='post' class="wpta-settings">
        <h1>WP Title Automatic</h1>
        <?php
        settings_fields('pluginPage');
        do_settings_sections('pluginPage');
        submit_button();
        ?>
    </form>
    <?php
}