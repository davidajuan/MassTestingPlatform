<form class="upload" method="post" action="">
  <div class="uploadStatus uploadStatus--success"></div>
  <div class="uploadStatus uploadStatus--error"></div>

  <div class="<?= count($available_services) === 1 && count($available_providers) === 1 ? "d-none" : "mb-4" ?>">
    <div class="formGroup">
      <label for="select-service">
          <strong><?= lang('select_service') ?></strong>
      </label>

      <select id="select-service" class="col-xs-12 selectInput">
          <?php
            foreach($available_services as $service) {
              echo '<option value="' . $service['id'] . '">' . $service['name'] . '</option>';
            }
          ?>
      </select>
    </div>

    <div class="formGroup">
        <label for="select-provider">
            <strong><?= lang('select_provider') ?></strong>
        </label>

        <select id="select-provider" class="col-xs-12 selectInput">
        <?php
            foreach($available_providers as $provider) {
              echo '<option value="' . $provider['id'] . '">' . $provider['first_name'] . ' ' . $provider['last_name'] .'</option>';
            }
          ?>
        </select>
    </div>
  </div>

  <i class="upload__icon fas fa-file-upload"></i>
  <input class="upload__input" type="file" name="file" id="file"/>
  <label class="upload__label" for="file">
    <strong>Choose a file</strong>
    <span> or drag it here</span>.
  </label>

  <div class="fileInformation hide"></div>

  <div class="progress hide">
    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
  </div>
  <input type="hidden" name="csrfToken" value="<?= $this->security->get_csrf_hash() ?>" />
  <button class="upload__button" type="submit">
    Upload
  </button>
  <p class="js-helperText mb-0 text-muted hide">
    <small>Make sure all the data below is correct before uploading.</small>
  </p>
</form>

<div class="uploadData hide"></div>
<div class="uploadDataError px-3"></div>

<script src="<?= asset_url('assets/js/file_upload.js') ?>"></script>
