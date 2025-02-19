<?php
/*
* File version: 2
*/
?>
<div class="directory-lite edit-details bootstrap-wrapper">

    <?php ldl_get_header(); ?>

    <h2><?php printf( __( 'Edit details for &ldquo;%s&rdquo;', 'ldd-directory-lite' ), ldl_get_value('title') ); ?></h2>

    <form id="submit-listing" name="submit-listing" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="action" value="edit-details">
        <?php echo wp_nonce_field('edit-details', 'nonce_field', 0, 0); ?>

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label" for="f_title"><?php esc_html_e('Title', 'ldd-directory-lite'); ?></label>
                        <input type="text" id="f_title" class="form-control" name="n_title" value="<?php echo esc_html(ldl_get_value('title')); ?>" required>
                        <?php echo ldl_get_error('title'); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label" for="f_category"><?php esc_html_e('Category', 'ldd-directory-lite'); ?></label>
                        <?php ldl_submit_multi_categories_dropdown( ldl_get_value('category'), 'category' ); ?>
                        <?php echo ldl_get_error('category'); ?>
                    </div>
                </div>
            </div>
            <div class="row bump-down">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label" for="f_description"><?php esc_html_e('Description', 'ldd-directory-lite'); ?></label>
                        <textarea id="f_description" class="form-control" name="n_description" rows="5" required><?php echo esc_textarea(ldl_get_value('description')); ?></textarea>
                        <?php echo ldl_get_error('description'); ?>
                        <p class="help-block"><?php printf(__('The description you include here will make up a major portion of your listing when viewed individually. You may use <a href="%s">markdown</a> to format your description, though we reserve the right to remove excess formatting before approving your listing.', 'ldd-directory-lite'), 'https://help.github.com/articles/markdown-basics'); ?></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label" for="f_summary"><?php esc_html_e('Summary', 'ldd-directory-lite'); ?></label>
                        <input type="text" id="f_summary" class="form-control" name="n_summary" value="<?php echo wp_kses_post(ldl_get_value('summary')); ?>" required>
                        <?php echo ldl_get_error('summary'); ?>
                        <p class="help-block"><?php esc_html_e('Please provide a short summary of your listing that will appear in search results.', 'ldd-directory-lite'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php ldl_get_template_part('frontend/edit', 'submit'); ?>
    </form>

</div>
