/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../scss/app.scss';

import $ from 'jquery';
import 'bootstrap';

// Funcionalidad que muestra el nombre de los ficheros seleccionados en los campos de texto personalizados de Bootstrap 4
$('.custom-file-input').each(function () {
    const $fileInput = $(this);
    const $fileLabel = $fileInput.parent().find('.custom-file-label').first();

    if ($fileLabel != null) {
        $fileInput.on('change', () => {
            const name = Array.from(this.files).map(file => file.name).join(', ').trim();
            $fileLabel.text(name.length > 0 ? name : $fileInput.attr('placeholder'));
        });
    }
});
