<div class="d-flex justify-content-between">
  <!-- BUSINESS SEARCH BAR -->
  <div id="filter-business" class="search justify-content-start align-items-center mx-0">
    <form method="get" id="business-request-search">
      <div class="d-flex align-items-center search__inputContainer">
        <input type="text" class="col key textInput search__input" placeholder="Search" value="<?= $_GET['query'] ?? '' ?>">
        <div class="input-group-addon">
          <div>
            <button class="filter btn btn--simple px-1" type="submit" title="<?= lang('filter') ?>">
              <i class="fas fa-search"></i>
            </button>
            <button class="clear btn btn--simple px-1" id="clear-filter" type="button" title="<?= lang('clear') ?>">
              <i class="fas fa-redo-alt"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="searchResults">
        <div class="searchResults__items"></div>
      </div>
    </form>
    <div class="dropdown px-3 mt-2 mt-md-0">
      <button class="btn btn--simple" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-filter"></i>
        <span data-filter="text">
            <?= lang('business_request_filter') ?>
        </span>
      </button>
      <div class="dropdown-menu" id="status-filter" aria-labelledby="dropdownMenuButton">
        <a class="dropdown-item" href="#" data-status="pending"><?= lang('business_request_filter_pending') ?></a>
        <a class="dropdown-item" href="#" data-status="active"><?= lang('business_request_filter_active') ?></a>
        <a class="dropdown-item" href="#" data-status="deleted"><?= lang('business_request_filter_denied') ?></a>
        <a class="dropdown-item" href="#" data-status="clear"><?= lang('business_request_filter_clear') ?></a>
      </div>
      <input type="hidden" name="requestStatus" value="<?= isset($_GET['status']) ? $_GET['status'] : '' ?>">
    </div>
  </div>

  <div id="report-business" class="search justify-content-end align-items-center mx-0">
    <button id="report-business-capacity" class="btn btn-primary" onclick="location.href='/report?name=businessCapacity'"><?= lang('br_download_capacity') ?></button>
  </div>
</div>

<div class="d-md-flex mb-5">
  <div class="table-responsive-md col-md-8 pr-0 tableFixedHeight mb-4">
    <table class="table table--interactive table-sm table-striped table-hover">
    <thead class="table__head-colored">
      <tr>
        <th scope="col"><?= lang('br_header_business_name') ?></th>
        <th scope="col"><?= lang('br_header_zip_code') ?></th>
        <th scope="col"><?= lang('br_header_date') ?></th>
        <th scope="col"><?= lang('br_header_status') ?></th>
        <th scope="col"><?= lang('br_header_requested') ?></th>
        <th scope="col"><?= lang('br_header_approved') ?></th>
        <th scope="col"><?= lang('br_header_priority') ?></th>
        <th scope="col"><?= lang('br_header_actions') ?></th>
      </tr>
    </thead>
    <tbody class="table__body">
      <?php foreach ($businesses as $idx => $business) { ?>
      <tr data-row="<?= $idx ?>">
        <th scope="row"><?= $business['business_name'] ?></th>
        <td><?= $business['zip_code'] ?></td>
        <td><?= date('m/d/y', strtotime($business['request_created'])) ?></td>
        <td>
            <?php
            switch ($business['status']) {
                case 'pending':
                    $class = 'colorYellowDarken';
                break;
                case 'active':
                    $class = 'colorGreen';
                break;
                case 'deleted':
                    $class = 'colorRed';
                break;
                default:
                    $class = '';
                break;
            }
            ?>
            <span class="<?= $class ?>">
                <?= $business['status'] == 'deleted' ? "Denied" : ucfirst($business['status']) ?>
            </span>
        </td>
        <td>
            <?= $business['slots_requested'] ?>
        </td>
        <td>
          <input class="textInput textInput--small" type="text" value="<?= $business['status'] !== 'pending' ? $business['slots_approved'] : '' ?>" id="approved-<?= $business['business_code'] ?>" data-mask="0000">
        </td>
        <td>
          <input class="ml-4" type="checkbox" id="priority-service-<?= $business['business_code'] ?>" <?= (int)$business['priority_service'] === 1 ? 'checked' : '' ?>>
        </td>
        <td>
          <button type="button" name="button" class="btn btn--simple px-0"
            data-id="approve-business-request"
            data-business-code="<?= $business['business_code'] ?>"
            data-business-name="<?= $business['business_name'] ?>"
            title="Approve">
            <i class="fas fa-check"></i>
          </button>
          <button type="button" name="button" class="btn btn--simple px-0 ml-2"
            data-id="delete-business-request"
            data-business-code="<?= $business['business_code'] ?>"
            data-business-name="<?= $business['business_name'] ?>"
            title="Deny">
            <i class="fas fa-ban"></i>
          </button>
          <div class="d-none" id="card-<?= $idx ?>">
            <div class="card py-0 px-0">
                <h3 class="h5 bgGray px-3 py-2">
                    <?= lang('business') ?>
                </h3>
                <div class="px-3 mb-2">
                    <p class="mb-0">
                        <strong><?= $business['business_name'] ?></strong>
                    </p>
                    <p class="mb-0">
                        <?= $business['business_phone'] ?>
                    </p>
                    <p class="mb-0">
                    <a href="mailto:<?= $business['email'] ?>">
                        <?= $business['email'] ?>
                    </a>
                    </p>
                    <p class="mb-0">
                        <?= $business['address'] ?>
                    <br>
                        <?= $business['city'] ?>, <?= $business['state'] ?> <?= $business['zip_code'] ?>
                    </p>
                </div>

                <h3 class="h5 bgGray px-3 py-2">
                    <?= lang('br_owner') ?>
                </h3>
                <div class="px-3 mb-2">
                    <p class="mb-0">
                    <strong><?= $business['owner_first_name'] ?> <?= $business['owner_last_name'] ?></strong>
                    </p>
                    <p class="mb-0">
                    <?= $business['mobile_phone'] ?>
                    </p>
                </div>

                <h3 class="h5 bgGray px-3 py-2">
                    <?= lang('br_requests') ?>
                </h3>
                <div class="px-3 mb-2">
                    <p class="mb-0">
                    <strong><?= $business['slots_requested'] ?></strong> <?= lang('br_header_requested') ?>
                    </p>
                    <p class="mb-0">
                    <strong><?= $business['slots_approved'] ?></strong> <?= lang('br_header_approved') ?>
                    </p>
                    <p class="mb-0">
                    <strong><?= $business['total_slots_used'] ?></strong> <?= lang('br_used') ?>
                    </p>
                </div>

                <h3 class="h5 bgGray px-3 py-2">
                    <?= lang('business_code') ?>
                </h3>
                <div class="px-3 mb-2">
                    <p class="mb-0">
                    <strong><?= $business['business_code'] ?></strong>
                    </p>
                </div>
            </div>
          </div>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
  </div>
  <div class="details col-md-4" id="details"></div>
</div>

<?php if (!empty($pagination->getPages())) { ?>
<nav aria-label="Page navigation example">
  <ul class="pagination justify-content-center">
    <li class="page-item <?= !$pagination->getPrevUrl() ? 'disabled': '' ?>">
      <a class="page-link" href="<?= $pagination->getPrevUrl(); ?>" tabindex="-1"><?= lang('previous') ?></a>
    </li>
    <?php foreach ($pagination->getPages() as $page){ ?>
    <li class="page-item <?= $page['isCurrent'] ? 'active' : ''?>">
      <a class="page-link" href="<?= $page['url']; ?>">
        <?= $page['num']; ?>
      </a>
    </li>
    <?php } ?>
    <li class="page-item <?= !$pagination->getNextUrl() ? 'disabled': '' ?>">
      <a class="page-link" href="<?= $pagination->getNextUrl(); ?>"><?= lang('next') ?></a>
    </li>
  </ul>
</nav>
<?php } ?>

<?php require 'delete_request_confirmation_modal.php' ?>
<?php require 'approve_request_confirmation_modal.php' ?>
<script>
    var GlobalVariables = {
      baseUrl             : <?= json_encode(config('base_url')) ?>,
      csrfToken           : <?= json_encode($this->security->get_csrf_hash()) ?>,
    };

    var EALang = <?= json_encode($this->lang->language) ?>;
    var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;
</script>
<script src="<?= asset_url('assets/js/backend_business_request_api.js') ?>"></script>
<script src="<?= asset_url('assets/js/book-validations.js') ?>"></script>
<script src="<?= asset_url('assets/js/backend_business_request.js') ?>"></script>
