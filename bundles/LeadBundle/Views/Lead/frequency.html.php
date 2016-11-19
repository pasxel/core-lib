<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId   = $lead->getId();
$leadName = $lead->getPrimaryIdentifier();
?>

    <?php echo $view['form']->start($form); ?>
<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#channels"><?php echo $view['translator']->trans('mautic.lead.preferred.channels'); ?></a></li>
    <li><a data-toggle="tab" href="#categories"><?php echo $view['translator']->trans('mautic.lead.preferred.categories'); ?></a></li>
    <li><a data-toggle="tab" href="#segments"><?php echo $view['translator']->trans('mautic.lead.preferred.segments'); ?></a></li>
</ul>

<div class="tab-content">
    <div id="channels" class="tab-pane fade in active">
        <table class="table" width="100%" id="contact-timeline">
            <thead >
            <tr >
                <th>
                    <input type="checkbox" id="lead_contact_frequency_rules_doNotContactChannels_0" name="check_all"
                           onclick="Mautic.togglePreferredChannel(<?php echo $leadId; ?>,this.value);" value="all">
                </th>
                <th>
                    <?php echo $view['translator']->trans('mautic.lead.preference.channels'); ?>
                </th>
                <th><?php echo $view['translator']->trans('mautic.lead.preferred.frequency'); ?></th>
                <th><?php echo $view['translator']->trans('mautic.lead.preferred.channels'); ?></th>
            </tr>
            </thead>
            <tbody >


            <?php foreach ($form['doNotContactChannels']->vars['choices'] as $channel): ?>
                <?php
                $contactMe     = isset($leadChannels[$channel->value]);
                $isContactable = $contactMe ? 'channel-enabled' : 'channel-disabled';
                $hidden        = $contactMe ? '' : 'hide';
                $checked       = $contactMe ? 'checked' : '';
                $disabled      = isset($leadChannels[$channel->value]) ? '' : 'disabled';
                ?>
                <tr>
                    <th style="vertical-align: top" class="col-md-1">
                        <input type="checkbox" id="<?php echo $channel->value ?>"
                               name="lead_contact_frequency_rules[doNotContactChannels][]" class="contact checkbox"
                               onclick="Mautic.togglePreferredChannel(<?php echo $leadId; ?>,this.value);"
                               value="<?php echo $channel->value ?>" <?php echo $checked; ?>>
                    </th>
                    <td class="col-md-1" style="vertical-align: top">
                        <div id="is-contactable-<?php echo $channel->value ?>" class="<?php echo $isContactable; ?> fw-sb">
                            <?php echo $channel->value; ?>
                        </div>
                    </td>
                    <td class="col-md-9" style="vertical-align: top">
                            <div>
                                <div class="pull-left">
                                    <?php echo $view['form']->widget($form['frequency_number_'.$channel->value]); ?>
                                    <?php echo $view['form']->label($form['frequency_time_'.$channel->value]); ?>
                                    <div class="frequency-select"><?php echo $view['form']->widget($form['frequency_time_'.$channel->value]); ?></div>
                                </div>
                            </div>
                    </td>
                <td class="col-md-1" style="vertical-align: top;">
                        <input type="radio" id="preferred_<?php echo $channel->value ?>"
                               name="lead_contact_frequency_rules[preferred_channel]" class="contact checkbox"
                               value="<?php echo $channel->value ?>" <?php if ($form['preferred_channel']->vars['value'] == $channel->value) {
                    echo $checked;
                } ?> <?php echo $disabled; ?>>

                </td>
                </tr>
                <tr style="border-top:none"><th style="border-top:none"></th>
                    <td  style="border-top:none"></td>
                    <td colspan="2" style="border-top:none">
                        <div id="frequency_<?php echo $channel->value; ?>" class="<?php echo $hidden; ?>" >
                            <div>
                                <label class="text-muted fw-n"><?php echo $view['translator']->trans('mautic.lead.frequency.dates.label'); ?></label>
                            </div>
                            <div>
                                <?php echo $view['form']->widget($form['contact_pause_start_date_'.$channel->value]); ?>
                                <div style="float:left;">
                                    <?php echo $view['form']->label($form['contact_pause_end_date_'.$channel->value]); ?>
                                </div>
                                    <?php echo $view['form']->widget($form['contact_pause_end_date_'.$channel->value]); ?>

                            </div>

                        </div>
                        <div class="clearfix"></div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="categories" class="tab-pane fade">
        <?php
        unset($form['preferred_channel']);
        unset($form['doNotContactChannels']); ?>

        <table class="table" width="100%" id="contact-timeline">
            <thead >
            <tr >
                <th>
                <?php echo $view['form']->row($form['global_categories']); ?>
                </th>
            </tr>
            </thead>
        </table>

    </div>
    <div id="segments" class="tab-pane fade">
        <table class="table" width="100%" id="contact-timeline">
            <thead >
            <tr >
                <th>
                    <?php echo $view['form']->row($form['lead_lists']); ?>
                </th>
            </tr>
            </thead>
        </table>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

