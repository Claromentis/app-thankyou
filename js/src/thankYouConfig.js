define(['jquery'], function ($) {
    var thankYouConfig = function () {

    };

    thankYouConfig.prototype.setTagsEnabled = function (enabled, success, error) {
        this.setConfigs({thankyou_core_values_enabled: enabled}, success, error);
    };

    thankYouConfig.prototype.setTagsMandatory = function (enabled, success, error) {
        this.setConfigs({'thankyou_core_values_mandatory': enabled}, success, error);
    };

    thankYouConfig.prototype.setConfigs = function (configs, success, error) {
        var ajaxArgs = {
            url: '/api/thankyou/v2/admin/config',
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(configs)
        };

        if (success) {
            ajaxArgs.success = success;
        }
        if (error) {
            ajaxArgs.error = error;
        }
        $.ajax(ajaxArgs);
    };

    return new thankYouConfig();
});
