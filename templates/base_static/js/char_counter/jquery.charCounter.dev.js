/*
 * Name    : jQuery CharCounter Plugin
 * Version : v2.2 - Modernized
 * Author  : Erhan BURHAN (eburhan)
 *
 * Copyright (c) 2010 eburhan
 * Licensed under MIT and GPL licenses
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */

(function($) {

    $.fn.charCounter = function(opt){

        // Default options
        var def = {
            limit: 250,
            message: 'remaining characters: #1',
            container: '<div></div>',
            warning: function(){}
        };

        // Fallback for legacy keys if provided in opt
        if (opt) {
            if (opt.mesaj && !opt.message) opt.message = opt.mesaj;
            if (opt.kapsa && !opt.container) opt.container = opt.kapsa;
            if (opt.uyari && !opt.warning) opt.warning = opt.uyari;
        }

        // Override default options with user options
        var settings = $.extend({}, def, opt); 

        // Apply operations for each element
        this.each(function(i, txtArea){
            var txtAreaId = $(txtArea).attr('id');
            var containerType  = typeof settings.container;
            var containerObj  = (containerType == 'object') ? settings.container : $(settings.container);
            var containerId   = containerObj.attr('id');

            // Generate ID if missing (JKK = jQuery Remaining Characters)
            if(! txtArea) {
                txtAreaId = 'text_' + (Math.random() * Math.pow(10,18));
                $(txtArea).attr('id', txtAreaId);
            }

            // Check character state on start
            charCounterCheck(txtArea);

            // Remaining character count in current element
            var remaining = charCounterCount(txtArea);

            // Set element container for remaining character count
            if(! containerId) {
                containerId = 'JKK_' + txtAreaId;
                containerObj.attr(containerId);
            }

            // Append new container if missing
            if(containerType == 'string')
                $(txtArea).after(containerObj);

            // Display remaining character count in container
            containerObj.attr('id', containerId).html( settings.message.replace(/#1/, remaining) );
            $(txtArea).keyup(function(){ charCounterPrint(containerId, txtArea) })
        });

        // Trim characters exceeding limit in textarea
        function charCounterCheck(obj) {
            $(obj).val( $(obj).val().substr(0, settings.limit) );
        }

        // Calculate remaining characters in textarea
        function charCounterCount(obj) {
            return settings.limit - $(obj).val().length;
        }

        // Render remaining character count below textarea
        function charCounterPrint(containerIdParam, obj) {
            var remaining = charCounterCount(obj);

            if( remaining < 0) {
                charCounterCheck(obj);
                remaining = 0;
                settings.warning();
            }

            $('#'+containerIdParam).html(settings.message.replace(/#1/, remaining));
        }

        // Return this for chaining
        return this;

    };// end of plugin

})(jQuery);
