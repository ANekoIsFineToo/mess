import '../scss/profile.scss';

import $ from 'jquery';

const $showUpdatePassword = $('#showUpdatePassword');
const $submitEditProfileForm = $('#submitEditProfileForm');

$submitEditProfileForm.on('click', async (e) => {
    e.preventDefault();
    $submitEditProfileForm.attr('disabled', true);
    $submitEditProfileForm.find('span').removeClass('d-none');

    const $form = $('#userEditForm');

    try {
        await $.ajax({url: $form.attr('action'), type: $form.attr('method'), data: $form.serialize()});
        location.reload();
    } catch (jqXHR) {
        if (jqXHR.status === 422) {
            $form.parent().html(jqXHR.responseText);

            if ($showUpdatePassword.attr('aria-expanded') === 'true') {
                $('#userEditFormPasswordFields').addClass('show');
            }
        }

        $submitEditProfileForm.attr('disabled', false);
        $submitEditProfileForm.find('span').addClass('d-none');
    }
});
