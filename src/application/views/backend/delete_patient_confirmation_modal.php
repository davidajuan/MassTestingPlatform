<div id="delete-patient-confirmation-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h4 class="modal-title"><?= lang('delete_patient') ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p><?= lang('delete_patient_body') ?></p>
                <form>
                    <div class="form-group">
                        <label for="delete-first-name"><?= lang('delete_first_name') ?></label>
                        <input type="text" id="delete-first-name" class="textInput" maxlength="120" data-validate="name" data-format-string="titleCase"/>
                        <span class="deleteErrorMessage"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-start">
                <button type="button" id="confirm-delete-patient" class="btn btn-primary"><?= lang('delete_patient_button') ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= lang('cancel') ?></button>
            </div>
        </div>
    </div>
</div>
