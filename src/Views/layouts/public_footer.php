<?php
use App\Core\CSRF;
use App\Core\Controller;
?>
<script>window.CSRF_TOKEN = <?php echo json_encode(CSRF::token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.6/purify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.1/marked.min.js"></script>
<script src="<?php echo Controller::asset('/assets/js/src/icons.js'); ?>"></script>
<script src="<?php echo Controller::asset('/assets/js/src/common.js'); ?>"></script>
