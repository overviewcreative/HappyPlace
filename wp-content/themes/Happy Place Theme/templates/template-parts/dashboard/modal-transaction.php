<?php
/**
 * Template part for displaying the transaction modal
 *
 * @package Happy_Place_Theme
 */
?>
<div id="transaction-modal" class="dashboard-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo esc_html__('Add New Transaction', 'happy-place'); ?></h2>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="transaction-form" class="dashboard-form">
                <div class="form-group">
                    <label for="transaction_title"><?php echo esc_html__('Transaction Title', 'happy-place'); ?></label>
                    <input type="text" id="transaction_title" name="title" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount"><?php echo esc_html__('Amount', 'happy-place'); ?></label>
                        <input type="number" id="amount" name="amount" min="0" step="1000" required>
                    </div>
                    <div class="form-group">
                        <label for="commission_rate"><?php echo esc_html__('Commission Rate (%)', 'happy-place'); ?></label>
                        <input type="number" id="commission_rate" name="commission_rate" min="0" max="100" step="0.1" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="closing_date"><?php echo esc_html__('Closing Date', 'happy-place'); ?></label>
                        <input type="date" id="closing_date" name="closing_date" required>
                    </div>
                    <div class="form-group">
                        <label for="transaction_status"><?php echo esc_html__('Status', 'happy-place'); ?></label>
                        <select id="transaction_status" name="status" required>
                            <option value="Pending"><?php echo esc_html__('Pending', 'happy-place'); ?></option>
                            <option value="In Process"><?php echo esc_html__('In Process', 'happy-place'); ?></option>
                            <option value="Completed"><?php echo esc_html__('Completed', 'happy-place'); ?></option>
                            <option value="Cancelled"><?php echo esc_html__('Cancelled', 'happy-place'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="property_address"><?php echo esc_html__('Property Address', 'happy-place'); ?></label>
                    <input type="text" id="property_address" name="property_address" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name"><?php echo esc_html__('Client Name', 'happy-place'); ?></label>
                        <input type="text" id="client_name" name="client" required>
                    </div>
                    <div class="form-group">
                        <label for="agent_name"><?php echo esc_html__('Agent Name', 'happy-place'); ?></label>
                        <input type="text" id="agent_name" name="agent" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes"><?php echo esc_html__('Notes', 'happy-place'); ?></label>
                    <textarea id="notes" name="notes" rows="4"></textarea>
                </div>
                <input type="hidden" name="id" value="">
                <?php wp_nonce_field('happy_place_transaction_nonce', 'transaction_nonce'); ?>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button cancel-btn"><?php echo esc_html__('Cancel', 'happy-place'); ?></button>
            <button type="submit" form="transaction-form" class="button button-primary save-btn"><?php echo esc_html__('Save Transaction', 'happy-place'); ?></button>
        </div>
    </div>
</div>
