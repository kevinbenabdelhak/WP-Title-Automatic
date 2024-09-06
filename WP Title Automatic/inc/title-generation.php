<?php


if (!defined('ABSPATH')) {
    exit; 
}


// Ajout des colonnes et boutons
add_filter('manage_posts_columns', 'WP_Title_Automatic_add_title_generate_button_column');
add_action('manage_posts_custom_column', 'WP_Title_Automatic_render_title_generate_button', 10, 2);
add_action('manage_pages_columns', 'WP_Title_Automatic_add_title_generate_button_column');
add_action('manage_pages_custom_column', 'WP_Title_Automatic_render_title_generate_button', 10, 2);

// Ajout de l'option groupée
add_action('admin_footer', 'WP_Title_Automatic_bulk_action_script');
function WP_Title_Automatic_bulk_action_script() {
    global $current_screen;
    if ($current_screen->base !== 'edit') {
        return;
    }
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#bulk-action-selector-top, #bulk-action-selector-bottom').append('<option value="generate_titles"><?php _e("Générer les titres", "textdomain"); ?></option>');

            $('body').on('click', '#doaction, #doaction2', function(e) {
                if ($('select[name="action"]').val() === 'generate_titles' || $('select[name="action2"]').val() === 'generate_titles') {
                    e.preventDefault();
                    var post_ids = [];
                    $('tbody input[type="checkbox"]:checked').each(function() {
                        post_ids.push($(this).val());
                    });

                    var index = 0;

                    function generateTitle() {
                        if (index < post_ids.length) {
                            var post_id = post_ids[index];
                            var $checkbox = $('tbody input[type="checkbox"][value="' + post_id + '"]');
                            var $row = $checkbox.closest('tr');
                            var postTitle = $row.find(".row-title").text().trim();
                            var consider_option = '<?php echo esc_js(get_option('wpta_settings')['wpta_consider_title'] ?? 'existing'); ?>';

                            var data = {
                                action: 'WP_Title_Automatic_get_title_suggestions',
                                post_id: post_id,
                                prompt: '<?php echo esc_js(get_option('wpta_settings')['wpta_prompt'] ?? ''); ?>',
                                suggestions: <?php echo json_encode(get_option('wpta_settings')['wpta_suggestions'] ?? []); ?>,
                                consider_option: consider_option,
                                title: postTitle
                            };

                            $.post(WPTitleAutomatic.ajaxurl, data, function(response) {
                                var suggestionData = JSON.parse(response);
                                var suggestions = suggestionData.suggestions;

                                if (suggestions && suggestions.length > 0) {
                                    var newTitle = suggestions[0];
                                    $.post(WPTitleAutomatic.ajaxurl, {
                                        action: 'WP_Title_Automatic_update_post_title',
                                        post_id: post_id,
                                        new_title: newTitle
                                    }, function() {
                                        $row.find(".row-title").text(newTitle);
                                        $row.css("background-color", "#fff2f2");
                                        setTimeout(function() {
                                            $row.css("background-color", "");
                                        }, 2000);
                                        index++;
                                        generateTitle(); // Appel récursif pour le prochain titre
                                    });
                                } else {
                                    index++;
                                    generateTitle(); // Passer à la prochaine publication
                                }
                            });
                        } else {
                            location.reload(); // Recharger la page après toutes les mises à jour
                        }
                    }

                    generateTitle(); // Démarrer le processus de génération
                }
            });
        });
    </script>
    <?php
}

function WP_Title_Automatic_add_title_generate_button_column($columns) {
    $columns['generate_title'] = __('Actions', 'textdomain');
    return $columns;
}
function WP_Title_Automatic_render_title_generate_button($column, $post_id) {
    if ($column === 'generate_title') {
        echo '<button class="gencode-generate-title-button" data-post-id="' . esc_attr($post_id) . '" style="background-color: #0073aa; color: white; border: none; padding: 5px 8px; cursor: pointer; margin-top: 5px !important; line-height: 22px;">' . __('Générer un titre', 'textdomain') . '</button>';
    }
}

// Enregistrement des actions AJAX
add_action('wp_ajax_WP_Title_Automatic_get_title_suggestions', 'WP_Title_Automatic_get_title_suggestions');
add_action('wp_ajax_WP_Title_Automatic_update_post_title', 'WP_Title_Automatic_update_post_title');

function WP_Title_Automatic_get_title_suggestions() {
    $title = sanitize_text_field($_POST['title']);
    $post_id = intval($_POST['post_id']);
    $prompt = sanitize_text_field($_POST['prompt']);
    $suggestions = isset($_POST['suggestions']) ? array_map('sanitize_text_field', $_POST['suggestions']) : [];
    $consider_option = sanitize_text_field($_POST['consider_option']);

    $first_300_words = '';

    // Récupération du contenu du post pour les 300 premiers mots ou titre existant
    if ($consider_option === 'first_300') {
        $post = get_post($post_id);
        if ($post) {
            $content = wp_strip_all_tags($post->post_content);
            $words = explode(' ', $content);
            $first_300_words = implode(' ', array_slice($words, 0, 300));
            $prompt .= "Contexte : " . $first_300_words . "\n";
        }
    } elseif ($consider_option === 'existing') {
        $post = get_post($post_id);
        if ($post) {
            $title = $post->post_title;
            $prompt .= "Titre existant : " . $title . "\n";
        }
    }

    // Ajout des suggestions au prompt
    if (!empty($suggestions)) {
        $prompt .= "\nSuggestions : " . implode(", ", $suggestions);
    }

    $suggestions = WP_Title_Automatic_call_openai_api($title, $prompt);
    echo json_encode(['suggestions' => $suggestions]);
    wp_die();
}

function WP_Title_Automatic_call_openai_api($title, $prompt) {
    $options = get_option('wpta_settings');
    $api_key = sanitize_text_field($options['wpta_api_key']); // Récupérer la clé API depuis les options
    $url = 'https://api.openai.com/v1/chat/completions';

    $post_data = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "( Voici un titre : \"$title\" )\nGénère un nouveau titre et inclus-le dans un json avec la variable \"title\"\n\nInformations supplémentaires : " . $prompt
                    ]
                ]
            ]
        ],
        'temperature' => 1,
        'max_tokens' => 100,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
        'response_format' => [
            'type' => 'json_object'
        ]
    ]);

    // Envoyer la requête à l'API d'OpenAI
    $response = wp_remote_post($url, [
        'body' => $post_data,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ]
    ]);

    if (is_wp_error($response)) {
        return ['Aucune suggestion disponible.'];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $suggestions = [];
    if (isset($data['choices']) && isset($data['choices'][0]['message']['content'])) {
        $content = trim($data['choices'][0]['message']['content']);
        $json_data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json_data['title'])) {
            $suggestions[] = $json_data['title'];
        }
    }

    return $suggestions;
}

function WP_Title_Automatic_update_post_title() {
    $post_id = intval($_POST['post_id']);
    $new_title = sanitize_text_field($_POST['new_title']);
    wp_update_post(['ID' => $post_id, 'post_title' => $new_title]);
    wp_die();
}