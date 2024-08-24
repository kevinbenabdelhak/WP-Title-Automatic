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
            consider_option: WPTitleAutomatic.settings.wpta_consider_title,
            nonce: WPTitleAutomatic.nonce
        };
        
        $.post(WPTitleAutomatic.ajaxurl, data, function(response) {
            var suggestionData = JSON.parse(response);
            var suggestions = suggestionData.suggestions;
            
            if (suggestions && suggestions.length > 0) {
                var newTitle = suggestions[0];
                $.post(WPTitleAutomatic.ajaxurl, {
                    action: "WP_Title_Automatic_update_post_title",
                    post_id: postId,
                    new_title: newTitle,
                    nonce: WPTitleAutomatic.nonce
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
});