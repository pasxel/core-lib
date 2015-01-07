<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(":$template:page.html.php");

$view['assets']->addScriptDeclaration("var mauticBasePath = '$basePath';");
$view['assets']->addScriptDeclaration("var mauticAjaxUrl = '" . $view['router']->generate("mautic_core_ajax") . "';");
$view['assets']->addCustomDeclaration($view['assets']->getSystemScripts(true, true));

$custom = <<<CUSTOM
mQuery(document).ready( function() {

    mQuery('.dropdown-toggle').dropdown();
    mQuery('[data-toggle="tooltip"]').tooltip();

    CKEDITOR.disableAutoInline = true;
    mQuery("div[contenteditable='true']").each(function (index) {
        var content_id = mQuery(this).attr('id');
        CKEDITOR.inline(content_id, {
            toolbar: 'advanced',
            on: {
                blur: function (event) {
                    var data = event.editor.getData();

                    mQuery.ajax({
                        url: mauticAjaxUrl + '?action=page:setBuilderContent',
                        type: "POST",
                        data: {
                            content: data,
                            slot:    content_id.replace("slot-", ""),
                            page:    mQuery('#mauticPageId').val()
                        },
                        dataType: "html"
                    });
                }
            }
        });
    });
    
    // add newProp (dot separated string) to obj with new value
    function addValueToObj(obj, newProp, value) {
        var path = newProp.split(":");
        for (var i = 0, tmp = obj; i < path.length - 1; i++) {
            if (typeof tmp[path[i]] === 'undefined') {
                tmp = tmp[path[i]] = {};
            } else {
                tmp = tmp[path[i]]
            }
        }
        tmp[path[i]] = value;
    }

    // Save slot config
    var slotConfigs = {};
    mQuery("[data-slot-config]").each(function (index) {
        var input = mQuery(this);
        input.blur(function() {
            var slot = input.attr('data-slot-config');
            var allSlotConfigs = mQuery('[data-slot-config=\"' + slot + '\"]');
            allSlotConfigs.each(function(index, value) { 
                element = mQuery(this);
                var slotConfigPath = element.attr('name');
                var value = element.val();

                if (typeof slotConfigs[slot] === 'undefined') {
                    slotConfigs[slot] = {};
                }

                addValueToObj(slotConfigs[slot], slotConfigPath, value);
            });
            mQuery.ajax({
                url: mauticAjaxUrl + '?action=page:setBuilderContent',
                type: "POST",
                data: {
                    content: JSON.stringify(slotConfigs[slot]),
                    slot:    slot,
                    page:    mQuery('#mauticPageId').val()
                },
                dataType: "json"
            });
        });
    });
});

var SlideshowManager = {};
SlideshowManager.toggleFileOpened = false;
SlideshowManager.toggleFileManager = function() {
    var listOfSlides = mQuery('.modal.slides-config .list-of-slides li:not(.active)');
    var activeSlide = mQuery('.modal.slides-config .list-of-slides li.active');
    var configFields = mQuery('.modal.slides-config .config-fields .row:not(:last-child)');
    var fileManager = mQuery('#fileManager');

    listOfSlides.animate({
        opacity: "toggle",
        padding: "toggle",
        height: "toggle"
    }, 300);
    configFields.animate({
        opacity: "toggle",
        padding: "toggle",
        height: "toggle"
    }, 300);
    fileManager.animate({
        height: "toggle",
        opacity: "toggle"
    }, 300);
    
    if (SlideshowManager.toggleFileOpened) {
        activeSlide.animate({
            borderRadius: "0px"
        }, 500, function() {
            activeSlide.removeAttr( 'style' );
        });
    } else {
        activeSlide.animate({
            borderRadius: "21px"
        }, 500);
    }

    SlideshowManager.toggleFileOpened = !SlideshowManager.toggleFileOpened;
}

SlideshowManager.preloadFileManager = function() {
    filebrowserImageBrowseUrl = mauticBasePath + '/app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=images';
    var iframe = $("<iframe id='filemanager_iframe' />").attr({src: filebrowserImageBrowseUrl});
    $("#fileManager").hide().append(iframe);
    iframe.load(function() {
        var fileManager = mQuery('#filemanager_iframe').contents().find('body');
        fileManager.click(function() {
            console.log('fileManager clicked');
            var copyBtn = fileManager.find('#copy-button');
            if (copyBtn.length) {
                mQuery('.tab-pane.active.in input.background-image').val(copyBtn.attr('data-clipboard-text'));
            }
        });
    });
}

CUSTOM;
$view['assets']->addScriptDeclaration($custom);

$css = <<<CSS
.mautic-editable { min-height: 75px; width: 100%; border: dashed 1px #000; margin-top: 3px; margin-bottom: 3px; }
.mautic-content-placeholder { height: 100%; width: 100%; text-align: center; margin-top: 25px; }
.mautic-editable.over-droppable { border: dashed 1px #4e5e9e; }
div[contentEditable=true]:empty:not(:focus):before{ content:attr(data-placeholder) }
.dropdown.slideshow-options {position: absolute;top: 0;left: 0;}
#slideshow-options {opacity: 0.7;}
#filemanager_iframe {width: 100%; height: 500px;}
.file-manager-toggle {margin-top: 24px;}
CSS;

$view['assets']->addStyleDeclaration($css);

//Set the slots
foreach ($slots as $slot => $slotConfig) {

    // backward compatibility - if slotConfig array does not exist
    if (is_numeric($slot)) {
        $slot = $slotConfig;
        $slotConfig = array();
    }

    // define default config if does not exist
    if (!isset($slotConfig['type'])) {
        $slotConfig['type'] = 'html';
    }

    if (!isset($slotConfig['placeholder'])) {
        $slotConfig['placeholder'] = 'mautic.page.builder.addcontent';
    }

    if ($slotConfig['type'] == 'html' || $slotConfig['type'] == 'text') {
        $value = isset($content[$slot]) ? $content[$slot] : "";
        $view['slots']->set($slot, "<div id=\"slot-{$slot}\" class=\"mautic-editable\" contenteditable=true data-placeholder=\"{$view['translator']->trans('mautic.page.builder.addcontent')}\">{$value}</div>");
    }

    if ($slotConfig['type'] == 'slideshow') {
        if (isset($content[$slot])) {
            $options = json_decode($content[$slot], true);
        } else {
            $options = array(
                'width' => '100%',
                'height' => '250px',
                'background-color' => 'transparent',
                'show-arrows' => false,
                'show-dots' => true,
                'interval' => 5000,
                'pause' => 'hover',
                'wrap' => true,
                'keyboard' => true,
                'slides' => array (
                    array (
                        'order' => 0,
                        'background-image' => 'http://placehold.it/1900x250/4e5d9d&text=Slide+One',
                        'content' => '',
                        'captionheader' => 'Caption 1'
                    ),
                    array (
                        'order' => 1,
                        'background-image' => 'http://placehold.it/1900x250/4e5d9d&text=Slide+Two',
                        'content' => '',
                        'captionheader' => 'Caption 2'
                    )
                )
            );
        }
        $options['slot'] = $slot;
        $options['public'] = false;

        // create config form
        $options['configForm'] = $formFactory->createNamedBuilder(
            null, 
            'slideshow_config', 
            array(), 
            array('data' => $options)
        )->getForm()->createView();

        // create slide config forms
        foreach ($options['slides'] as $key => &$slide) {
            $slide['key'] = $key;
            $slide['slot'] = $slot;
            $slide['form'] = $formFactory->createNamedBuilder(
                null, 
                'slideshow_slide_config', 
                array(), 
                array('data' => $slide)
            )->getForm()->createView();
        }

        $view['slots']->set($slot, $view->render('MauticPageBundle:Page:Slots/slideshow.html.php', $options));
    }
}

//add builder toolbar
$view['slots']->start('builder');?>
<input type="hidden" id="mauticPageId" value="<?php echo $page->getSessionId(); ?>" />
<?php
$view['slots']->stop();
?>
