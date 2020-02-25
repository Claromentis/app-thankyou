define(['jquery'], function () {
    var repository = function () {
    };

    repository.prototype.get = function (id, thanked, tags, success, error) {
        var url = '/api/thankyou/v2/thanks/';
        var queryParameters = [];

        if (!Number.isNaN(id)) {
            url += id;
        }
        if (thanked) {
            queryParameters.push('thanked');
        }
        if (tags) {
            queryParameters.push('tags');
        }

        var queryParametersCount = queryParameters.length;
        if (queryParametersCount > 0) {
            url += '?';
            for (var offset = 0; offset < queryParametersCount; offset++) {
                url += queryParameters[offset] + '=1';
                if (offset !== queryParametersCount - 1) {
                    url += '&';
                }
            }
        }

        var ajaxArgs = {
            url: url
        };

        if (success) {
            ajaxArgs.success = success;
        }
        if (error) {
            ajaxArgs.error = error;
        }

        $.ajax(ajaxArgs);
    };

    repository.prototype.save = function (id, thankYouNote, success, error) {
        var url = '/api/thankyou/v2/thankyou';
        if (id !== null) {
            url += '/' + id;
        }

        var ajaxArgs = {
            url: url,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(thankYouNote)
        };

        if (success) {
            ajaxArgs.success = success;
        }
        if (error) {
            ajaxArgs.error = error;
        }

        $.ajax(ajaxArgs);
    };

    repository.prototype.delete = function (id, success, error) {
        var ajaxArgs = {
            url: '/api/thankyou/v2/thankyou/' + id,
            type: 'DELETE'
        };

        if (success) {
            ajaxArgs.success = success
        }
        if (error) {
            ajaxArgs.error = error;
        }

        $.ajax(ajaxArgs);
    };

    return new repository();
});
