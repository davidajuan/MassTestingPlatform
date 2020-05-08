<div id="approve-request-confirmation-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h4 class="modal-title">
                    Approve Business Request Confirmation
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                  Approve <span class="font-weight-bold" id="approve-request-slots-approved"></span> requests for <span class="font-weight-bold" id="approve-request-business-name"></span>
                </p>
            </div>
            <div class="modal-footer justify-content-start">
                <input type="hidden" id="approve-request-business-code">
                <input type="hidden" id="approve-priority-service">
                <button type="button" id="confirm-approve-request" class="btn btn-primary">Confirm</button>
                <button type="button" class="btn btn--simple" data-dismiss="modal">Cancel</button>
            </div>
            <div class="modalSpinner hide">
                <span class="spinner spinner--large"></span>
            </div>
        </div>
    </div>
</div>
