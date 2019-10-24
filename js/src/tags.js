define(['jquery'], function ($) {
    var Tags = function () {
        this.button_class_active = '.js-tag-admin-active';
        this.button_class_edit = '.js-tag-admin-edit';
        this.button_class_reset = '.js-tag-admin-reset';
        this.button_next = $('#tags_admin_next');
        this.button_previous = $('#tags_admin_previous');
        this.button_save = $('#tags_admin_save');
        this.button_cancel = $('#tags_admin_cancel');
        this.displayed_tags = {};
        this.limit = 1;
        this.offset = 0;
        this.page = 1;
        this.page_count = 1;
        this.template_tag_admin = $('#template_tag_admin');
        this.tags_list = $('#tags_admin_list');
        this.loaded_tags = {};
        this.modified_tags = {};

        this.tags_list.on('click', this.button_class_edit, function () {
            tags_admin.editTag(this.closest('.js-tag-admin').getAttribute('data-id'));
        });

        this.tags_list.on('click', this.button_class_active, function () {
            var id = this.closest('.js-tag-admin').getAttribute('data-id');
            var tag = JSON.parse(JSON.stringify(tags_admin.getTag(id)));
            tag.active = !tag.active;
            tags_admin.storeEdit(id, tag);
        });

        this.tags_list.on('click', this.button_class_reset, function () {
            var id = this.closest('.js-tag-admin').getAttribute('data-id');
            tags_admin.resetEdit(id);
        });
    };

    Tags.prototype.changePage = function (page_number) {
        //TODO: Add a shortcut so that loadTags is only run if we know there are tags not in loaded_tags. Not urgent, just speedier.
        var self = this;
        this.page = page_number;
        this.offset = (self.page - 1) * this.limit;
        this.loadTags(this.limit, this.offset, function (tags) {
            self.setTags(tags)
        });

        this.checkPageNavigation();
    };

    Tags.prototype.createTag = function (id, tag) {
        var args = {
            id: id,
            name: tag.name,
            active: tag.active,
            resetable: (id in this.modified_tags)
        };
        return _.template(this.template_tag_admin.html())(args);
    };

    Tags.prototype.editTag = function (id) {
        console.log(id);
    };

    Tags.prototype.loadTags = function (limit, offset, callback) {
        $.ajax('/api/thankyou/v2/tags?limit=' + limit + '&offset=' + offset).done(function (tags) {
            if (typeof callback === 'function') {
                callback(tags);
            }
        });
    };

    Tags.prototype.loadPageCount = function () {
        var self = this;
        $.ajax('/api/thankyou/v2/tags/total').done(function (total) {
            self.page_count = Math.ceil(total/self.limit);
            self.checkPageNavigation();
        });
    };

    Tags.prototype.getTag = function (id) {
        var tag = {};
        if (id in this.modified_tags) {
            tag = this.modified_tags[id];
        } else {
            tag = this.loaded_tags[id];
        }
        return tag;
    };

    Tags.prototype.resetAll = function () {
        this.modified_tags = {};
        this.setTags(this.displayed_tags)
        this.checkModified();
    };

    Tags.prototype.resetEdit = function (id) {
        delete this.modified_tags[id];
        if (id in this.displayed_tags) {
            this.setTags(this.displayed_tags);
        }
        this.checkModified();
    };

    Tags.prototype.save = function () {
        console.log(this.modified_tags);
    };

    Tags.prototype.setTags = function (tags) {
        this.displayed_tags = tags;
        var tags_list_html = '';
        for (var id in tags) {
            if (!(id in this.loaded_tags)) {
                this.loaded_tags[id] = tags[id];
            }
            var tag = this.getTag(id);
            tags_list_html += this.createTag(id, tag);
        }
        this.tags_list.html(tags_list_html);
    };

    Tags.prototype.storeEdit = function (id, tag) {
        this.modified_tags[id] = tag;

        if (_.isEqual(this.modified_tags[id], this.loaded_tags[id])) {
            this.resetEdit(id);
        } else {
            if (id in this.displayed_tags) {
                this.setTags(this.displayed_tags);
            }
            this.checkModified();
        }
    };

    Tags.prototype.checkModified = function () {
        if (Object.keys(this.modified_tags).length !== 0) {
            this.button_save.show();
            this.button_cancel.show();
        } else {
            this.button_save.hide();
            this.button_cancel.hide();
        }
    };

    Tags.prototype.checkPageNavigation = function () {
        var page_number = this.page;
        if (page_number === 1) {
            this.button_previous.hide();
        } else {
            this.button_previous.show();
        }

        if (page_number === this.page_count) {
            this.button_next.hide();
        } else {
            this.button_next.show();
        }
    };

    var tags_admin = new Tags();

    tags_admin.loadPageCount();

    tags_admin.changePage(tags_admin.page);

    tags_admin.button_next.click(function () {
        tags_admin.changePage(tags_admin.page + 1)
    });
    tags_admin.button_previous.click(function () {
        tags_admin.changePage(tags_admin.page - 1)
    });
    tags_admin.button_save.click(function () {
        tags_admin.save();
    });
    tags_admin.button_cancel.click(function () {
        tags_admin.resetAll();
    });

    return tags_admin;
});
