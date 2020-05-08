<div id="delete-request-confirmation-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h4 class="modal-title">
                  Deny Business Request
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                  Are you sure you want to deny the business request for <span class="font-weight-bold" id="delete-request-business-name"></span>?
                </p>
            </div>
            <div class="modal-footer justify-content-start">
                <input type="hidden" id="delete-request-business-code">
                <button type="button" id="confirm-delete-request" class="btn btn-primary">Yes</button>
                <button type="button" class="btn btn--simple" data-dismiss="modal">No</button>
            </div>
            <div class="modalSpinner hide">
                <span class="spinner spinner--large"></span>
            </div>
        </div>
    </div>
</div>
