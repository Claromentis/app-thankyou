define(['jquery'], function ($) {
    var Tags = function () {
        this.class_editable_field = '.js-tag-editable-field';
        this.class_row = '.js-tag-admin';
        this.button_class_active = '.js-tag-admin-active-button';
        this.button_class_edit = '.js-tag-admin-edit-button';
        this.button_class_reset = '.js-tag-admin-reset';
        this.button_next = $('#tags_admin_next');
        this.button_previous = $('#tags_admin_previous');
        this.button_save = $('#tags_admin_save');
        this.button_cancel = $('#tags_admin_cancel');
        this.displayed_tags = {};
        this.limit = 2;
        this.offset = 0;
        this.page = 1;
        this.page_count = 1;
        this.template_tag_admin = $('#template_tag_admin');
        this.tags_list = $('#tags_admin_list');
        this.loaded_tags = {};
        this.unlocked_tags = {};
        this.modified_tags = {};
        this.editable_field_names = [];

        this.tags_list.on('click', this.button_class_edit, function () {
            tags_admin.toggleEditMode(this.closest(tags_admin.class_row).getAttribute('data-id'));
        });

        this.tags_list.on('click', this.button_class_active, function () {
            var id = this.closest(tags_admin.class_row).getAttribute('data-id');
            var tag = JSON.parse(JSON.stringify(tags_admin.getTag(id)));
            tag.active = !tag.active;
            tags_admin.storeEdit(id, tag);
        });

        this.tags_list.on('click', this.button_class_reset, function () {
            var id = this.closest(tags_admin.class_row).getAttribute('data-id');
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
        var edit_mode = (id in this.unlocked_tags);
        var active = tag.active;
        var modified = (id in this.modified_tags);

        //FIX ME!
        var metadata = tag.metadata;
        var bg_colour = null;
        if (metadata !== null && 'bg_colour' in metadata) {
            tag.bg_colour = metadata.bg_colour;
        }

        var template = $($.parseHTML(_.template(this.template_tag_admin.html())({})));

        template.find(this.class_editable_field).prop('disabled', !edit_mode);
        template.find(this.button_class_edit).text(edit_mode ? 'Save' : 'Edit');
        template.find(this.button_class_active).text(active ? 'Disable' : 'Enable');
        if (modified) {
            template.find(this.button_class_reset).show();
        } else {
            template.find(this.button_class_reset).hide();
        }

        template.attr('data-id', id);

        this.fillRowFields(template, tag);

        return template;
    };

    Tags.prototype.fillRowFields = function (template, tag) {
        template.find(this.class_editable_field).each(function () {
            var field = $(this);
            var type = field.attr('type');
            var name = field.attr('data-name');
            var value = (name in tag ? tag[name] : null);

            if (type === 'text') {
                field.val(value);
            } else if (type === 'checkbox') {
                field.attr('checked', value);
            } else {
                console.log('Unsupported field type ' + type);
            }
        });
    };

    Tags.prototype.fillTagFromRow = function (row, tag) {
        row.find(this.class_editable_field).each(function () {
            var field = $(this);
            var type = field.attr('type');
            var name = field.attr('data-name');

            var value = null;
            if (type === 'text') {
                value = field.val();
                field.val(value);
            } else if (type === 'checkbox') {
                value = field.prop('checked');
            } else {
                console.log('Unsupported field type ' + type);
            }
            console.log(value);

            tag[name] = value;
        });
    };

    Tags.prototype.toggleEditMode = function (id) {
        if (id in this.unlocked_tags) {
            this.updateTagFromForm(id);
            delete this.unlocked_tags[id];
        } else {
            this.unlocked_tags[id] = true;
        }

        if (id in this.displayed_tags) {
            this.setTags(this.displayed_tags);
        }
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
            self.page_count = Math.ceil(total / self.limit);
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

    Tags.prototype.getModifiedTag = function (id) {
        if (!(id in this.modified_tags)) {
            this.modified_tags[id] = JSON.parse(JSON.stringify(this.loaded_tags[id]));
        }
        return this.modified_tags[id];
    };

    Tags.prototype.updateTagFromForm = function (id) {
        var row = $('.js-tag-admin[data-id=' + id + ']');
        var tag = this.getModifiedTag(id);

        this.fillTagFromRow(row, tag);
        console.log(tag);
        console.log(this.loaded_tags[id]);

        if (_.isEqual(tag, this.loaded_tags[id])) {
            this.resetEdit(id);
        } else {
            if (id in this.displayed_tags) {
                this.setTags(this.displayed_tags);
            }
            this.checkModified();
        }
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
        this.tags_list.empty();
        for (var id in tags) {
            if (!(id in this.loaded_tags)) {
                this.loaded_tags[id] = tags[id];
            }
            var tag = this.getTag(id);
            this.tags_list.append(this.createTag(id, tag));
        }
    };

    Tags.prototype.storeEdit = function (id, tag) {
        this.modified_tags[id] = tag;

        if (_.isEqual(this.modified_tags[id], this.loaded_tags[id])) {
            this.resetEdit(id);
        } else {
            if (id in this.displayed_tags) {
                this.setTags(this.displayed_tags);
            }
        }
        this.checkModified();
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

    Tags.prototype.getFieldsFromTemplate = function () {
        var template = $($.parseHTML(this.template_tag_admin.html()));
        var fields = [];
        template.find(this.class_editable_field).each(function () {
            fields.push($(this).attr('data-name'));
        });
        this.editable_field_names = fields;
    };

    var tags_admin = new Tags();

    tags_admin.getFieldsFromTemplate();

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
