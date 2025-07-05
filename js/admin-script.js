jQuery(document).ready(function($) {
    // Code pour le bouton de génération de titre
    $(".gencode-generate-title-button").on("click", function(event) {
        event.preventDefault();
        var postId = $(this).data("post-id");
        var $row = $(this).closest("tr");
        var postTitle = $row.find(".row-title").text().trim();
        
        let data = {
            action: "WP_Title_Automatic_get_title_suggestions",
            title: postTitle,
            post_id: postId,
            prompt: WPTitleAutomatic.settings.wpta_prompt,
            suggestions: WPTitleAutomatic.settings.wpta_suggestions,
            consider_option: WPTitleAutomatic.settings.wpta_consider_title
        };
        
        $.post(WPTitleAutomatic.ajaxurl, data, function(response) {
            var suggestionData = JSON.parse(response);
            var suggestions = suggestionData.suggestions;
            
            if (suggestions && suggestions.length > 0) {
                var newTitle = suggestions[0];
                $.post(WPTitleAutomatic.ajaxurl, {
                    action: "WP_Title_Automatic_update_post_title",
                    post_id: postId,
                    new_title: newTitle
                }, function() {
                    $row.find(".row-title").text(newTitle);
                    $row.css("background-color", "#fff2f2");
                    setTimeout(function() {
                        $row.css("background-color", "");
                    }, 2000);
                });
            } else {
                alert("Aucune suggestion disponible.");
            }
        }).fail(function(xhr, status, error) {
            alert("Erreur lors de la récupération des suggestions.");
        });
    });

    // Code pour les actions groupées
    $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function() {
        if ($(this).val() === 'generate_titles') {
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
                    var consider_option = WPTitleAutomatic.settings.wpta_consider_title;

                    var data = {
                        action: 'WP_Title_Automatic_get_title_suggestions',
                        post_id: post_id,
                        prompt: WPTitleAutomatic.settings.wpta_prompt,
                        suggestions: WPTitleAutomatic.settings.wpta_suggestions,
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