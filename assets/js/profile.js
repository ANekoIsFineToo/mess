import '../scss/profile.scss';

import feather from 'feather-icons';
import $ from 'jquery';

import { debounce } from './utils';

const $showUpdatePassword = $('#showUpdatePassword');
const $submitEditProfileForm = $('#submitEditProfileForm');

async function onSubmitEditProfile() {
    $submitEditProfileForm.attr('disabled', true);
    $submitEditProfileForm.find('span').removeClass('d-none');

    const $form = $('#userEditForm');

    try {
        await $.ajax({ url: $form.attr('action'), type: $form.attr('method'), data: $form.serialize() });
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
}

$submitEditProfileForm.on('click', onSubmitEditProfile);

const $addFriendForm = $('#addFriendForm');
const $addFriendInput = $('#addFriendInput');
const $addFriendLoading = $('#addFriendLoading');
const $addFriendNotFound = $('#addFriendNotFound');
const $addFriendResult = $('#addFriendResult');

async function onAddFriendSearch() {
    const username = $addFriendInput.val();

    if (username.length >= 3) {
        $addFriendLoading.removeClass('d-none');
        $addFriendNotFound.addClass('d-none');
        $addFriendResult.addClass('d-none');

        try {
            const foundUsers = await $.ajax({
                url: $addFriendForm.attr('action'),
                type: $addFriendForm.attr('method'),
                data: $addFriendForm.serialize()
            });

            $addFriendResult.removeClass('d-none');
            $addFriendResult.empty().html(foundUsers);
            feather.replace();

            $('.add-friend-button').tooltip();
        } catch (jqXHR) {
            if (jqXHR.status === 404) {
                $addFriendNotFound.removeClass('d-none');
            }

            // TODO: Controlar este error
            console.error(jqXHR);
        } finally {
            $addFriendLoading.addClass('d-none');
        }
    }
}

$addFriendInput.on('input', debounce(300, onAddFriendSearch));
