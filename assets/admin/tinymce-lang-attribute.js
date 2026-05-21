/**
 * TinyMCE plugin — bouton « lang ».
 * Wrappe la sélection dans <span lang="xx">…</span> pour l'a11y (lecteurs d'écran).
 * Enregistré côté PHP dans inc/tinymce-lang-button.php.
 */
(function () {
    if (typeof tinymce === 'undefined') {
        return;
    }

    tinymce.PluginManager.add('lb3_lang_attribute', function (editor) {
        editor.addButton('lb3_lang_attribute', {
            text: 'lang',
            tooltip: 'Marquer la sélection dans une autre langue (a11y)',
            onclick: function () {
                var content = editor.selection.getContent({ format: 'html' }) || '';
                if (!content) {
                    editor.windowManager.alert('Sélectionne d\'abord le texte à marquer.');
                    return;
                }

                editor.windowManager.open({
                    title: 'Langue du fragment',
                    body: [
                        {
                            type: 'textbox',
                            name: 'lang',
                            label: 'Code langue (ex : en, it, de, es)',
                            value: 'en',
                        },
                    ],
                    onsubmit: function (e) {
                        var lang = (e.data.lang || '').trim().toLowerCase();
                        if (!/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/i.test(lang)) {
                            editor.windowManager.alert('Code langue invalide. Utilise un code BCP 47 (en, it, de, en-US, pt-BR…).');
                            return;
                        }

                        // Échappe les " dans le contenu HTML — improbable mais safe.
                        var escaped = lang.replace(/"/g, '&quot;');
                        editor.selection.setContent(
                            '<span lang="' + escaped + '">' + content + '</span>'
                        );
                    },
                });
            },
        });
    });
})();
