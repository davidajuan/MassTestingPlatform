<div class="p-3">
    <div class="d-flex align-items-center mb-5">
        <i class="fas fa-3x fa-clipboard-check mr-3 colorGreen"></i>
        <h1 class="h3 mb-0 colorGreen">Business Request <?= $manage_mode ? "Updated" : "Created" ?></h1>
    </div>
    <div class="mb-4">
        <h2 class="mb-3">Business Details</h2>
        <p class="mb-3">
            <span class="font-weight-bold">Business Name</span>
            <span class="d-block">
                <?= $business['business_name'] ?>
            </span>
        </p>
        <p class="mb-3">
            <span class="font-weight-bold">Owner/Authorized Representative</span>
            <span class="d-block">
                <?= $business['owner_first_name'] ?> <?= $business['owner_last_name'] ?>
            </span>
        </p>
        <p class="mb-3">
            <span class="font-weight-bold">Business Phone Number</span>
            <span class="d-block">
                <?= $business['business_phone'] ?>
            </span>
        </p>

        <?php if($business['mobile_phone']) { ?>
        <p class="mb-3">
            <span class="font-weight-bold">Cell Phone Number</span>
            <span class="d-block">
                <?= $business['mobile_phone'] ?>
            </span>
            <span class="d-block">
                <?= ($business['consent_sms'] ? '* Consented to recieve sms messages' : '') ?>
            </span>
        </p>
        <?php } ?>

        <?php if($business['email']) { ?>
        <p class="mb-3">
            <span class="font-weight-bold">Email</span>
            <span class="d-block">
                <?= $business['email'] ?>
            </span>
            <span class="d-block">
                <?= ($business['consent_email'] ? '* Consented to recieve emails' : '') ?>
            </span>
        </p>
        <?php } ?>

        <p class="mb-3">
            <span class="font-weight-bold">Business Address</span>
            <span class="d-block"><?= $business['address'] ?></span>
            <span>
                <?= $business['city'] ?>,
                <?= $business['state'] ?>
                <?= $business['zip_code'] ?>
            </span>
        </p>
        <?php if(!empty($business_request)) { ?>
        <p class="mb-3">
            <span class="font-weight-bold">Slots Requested</span>
            <span class="d-block">
            <?= $business_request['slots_requested'] ?>

            </span>
        </p>
        <?php } ?>
    </div>
    <a href="<?= site_url('backend/business') ?>" class="btn btn-primary mb-3">
        Next Business
    </a>
    <?php
        // Display exceptions (if any).
        if (isset($exceptions)) {
            echo '<div class="col-xs-12" style="margin:10px">';
            echo '<h4>Unexpected Errors</h4>';
            foreach($exceptions as $exc) {
                echo exceptionToHtml($exc);
            }
            echo '</div>';
        }
    ?>
</div>
