define(['jquery', '../../css/style.scss'], function ($) {
    var ListableItemsAdmin = function () {
        this.new_item_key_preface = 'new-';
        this.row_new_class = 'js-listable-item-admin-new';
        this.row_modified_class = 'js-listable-item-admin-modified';
        this.row_deleted_class = 'js-listable-item-admin-deleted';
        this.class_editable_field = 'js-listable-item-admin-editable-field';
        this.row_class = 'js-listable-item-admin-row';
        this.button_create = $('#js-listable-item-admin-create');
        this.button_class_edit = 'js-listable-item-admin-edit-button';
        this.button_class_reset = 'js-listable-item-admin-reset-button';
        this.button_class_delete = 'js-listable-item-admin-delete-button';
        this.button_next = $('#js-listable-item-admin-nav-next');
        this.button_previous = $('#js-listable-item-admin-nav-previous');
        this.button_save = $('#js-listable-item-admin-save');
        this.button_cancel = $('#js-listable-item-admin-cancel');
        this.displayed_ids = [];
        this.limit = 2;
        this.offset = 0;
        this.page = 1;
        this.page_count = 1;
        this.html_template = $('#js-listable-item-admin-item-template');
        this.items_list = $('#js-listable-item-admin-list');
        this.loaded_items = {};
        this.unlocked_items = {};
        this.modified_items = {};
        this.deleted_items_ids = [];
        this.editable_field_names = [];
        this.new_item_ids = [];
        this.new_offset = 1;

        this.items_list.on('click', '.' + this.button_class_edit, function () {
            items_admin.toggleEditMode(this.closest('.' + items_admin.row_class).getAttribute('data-id'));
        });

        this.items_list.on('click', '.' + this.button_class_reset, function () {
            var id = this.closest('.' + items_admin.row_class).getAttribute('data-id');
            items_admin.resetEdit(id);
        });

        this.items_list.on('click', '.' + this.button_class_delete, function () {
            var id = this.closest('.' + items_admin.row_class).getAttribute('data-id');
            items_admin.toggleDeleteItem(id);
        });
    };

    ListableItemsAdmin.prototype.createNew = function () {
        var template = $($.parseHTML(_.template(this.html_template.html())({})));

        var item = {};
        this.fillItemFromRow(template, item);

        var key = this.new_item_key_preface + this.new_offset;
        this.new_offset++;

        this.loaded_items[key] = item;

        this.new_item_ids.unshift(key);

        this.displayed_ids.unshift(key);

        this.checkModified();

        this.toggleEditMode(key);
    };

    ListableItemsAdmin.prototype.changePage = function (page_number) {
        //TODO: Add a shortcut so that loadItems is only run if we know there are items not in loaded_items. Not urgent, just speedier.
        var self = this;
        this.page = page_number;
        this.offset = (self.page - 1) * this.limit;
        this.loadItems(this.limit, this.offset, function (items) {

            self.displayed_ids = [];

            for (var offset in self.new_item_ids) {
                self.displayed_ids.push(self.new_item_ids[offset]);
            }

            for (var id in items) {
                self.loaded_items[id] = items[id];
                self.displayed_ids.push(id);
            }
            self.refreshDisplay();
        });

        this.checkPageNavigation();
    };

    ListableItemsAdmin.prototype.loadItems = function (limit, offset, callback) {
        var self = this;
        $.ajax('/api/thankyou/v2/tags?limit=' + limit + '&offset=' + offset).done(function (items) {
            if (typeof callback === 'function') {
                callback(items);
            }
        });
    };

    ListableItemsAdmin.prototype.fillItemFromRow = function (row, item) {
        row.find('.' + this.class_editable_field).each(function () {
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

            item[name] = value;
        });
    };

    ListableItemsAdmin.prototype.toggleEditMode = function (id) {
        if (id in this.unlocked_items) {
            this.updateItemFromForm(id);
            delete this.unlocked_items[id];
        } else {
            this.unlocked_items[id] = true;
        }

        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.toggleDeleteItem = function (id) {
        if (this.new_item_ids.includes(id)) {
            var new_offset = this.new_item_ids.indexOf(id);
            this.new_item_ids.splice(new_offset, 1);

            var display_offset = this.displayed_ids.indexOf(id);
            this.displayed_ids.splice(display_offset, 1);

            delete this.modified_items[id];
        } else if (this.deleted_items_ids.includes(id)) {
            this.deleted_items_ids.splice(this.deleted_items_ids.indexOf(id));
        } else {
            this.deleted_items_ids.push(id);
        }

        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.loadPageCount = function () {
        var self = this;
        $.ajax('/api/thankyou/v2/tags/total').done(function (total) {
            self.page_count = Math.ceil(total / self.limit);
            self.checkPageNavigation();
        });
    };

    ListableItemsAdmin.prototype.getItem = function (id) {
        var item = {};
        if (id in this.modified_items) {
            item = this.modified_items[id];
        } else {
            item = this.loaded_items[id];
        }
        return item;
    };

    ListableItemsAdmin.prototype.getModifiedItem = function (id) {
        if (!(id in this.modified_items)) {
            this.modified_items[id] = JSON.parse(JSON.stringify(this.loaded_items[id]));
        }
        return this.modified_items[id];
    };

    ListableItemsAdmin.prototype.updateItemFromForm = function (id) {
        var row = $('.' + this.row_class + '[data-id=' + id + ']');
        var item = this.getModifiedItem(id);

        this.fillItemFromRow(row, item);

        if (_.isEqual(item, this.loaded_items[id])) {
            this.resetEdit(id);
        } else {
            this.refreshDisplay();
        }
    };

    ListableItemsAdmin.prototype.resetAll = function () {
        this.modified_items = {};

        for (var offset in this.new_item_ids) {
            var display_offset = this.displayed_ids.indexOf(this.new_item_ids[offset]);
            this.displayed_ids.splice(display_offset, 1);
        }
        this.new_item_ids = [];
        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.resetEdit = function (id) {
        console.log('resetEdit');
        console.log(id);
        delete this.modified_items[id];
        if (this.new_item_ids.includes(id)) {
            this.getModifiedItem(id);
        }
        this.refreshDisplay();
    };

    ListableItemsAdmin.prototype.save = function () {
        console.log(this.modified_items);
        console.log(this.deleted_items_ids);
    };

    ListableItemsAdmin.prototype.refreshDisplay = function () {
        var displayed_ids = this.displayed_ids;
        this.items_list.empty();
        for (var offset in displayed_ids) {
            var item = this.getItem(displayed_ids[offset]);
            this.items_list.append(this.displayRow(displayed_ids[offset], item));
        }

        this.checkModified();
    };

    ListableItemsAdmin.prototype.displayRow = function (id, item) {
        var new_item = this.new_item_ids.includes(id);
        var edit_mode = (id in this.unlocked_items);
        var modified = (id in this.modified_items);
        var deleted = this.deleted_items_ids.includes(id);

        var template = $($.parseHTML(_.template(this.html_template.html())({})));

        if (new_item) {
            template.addClass(this.row_new_class);
        } else if (deleted) {
            template.addClass(this.row_deleted_class);
        } else if (modified) {
            template.addClass(this.row_modified_class);
        }
        console.log(deleted);

        var edit_button = template.find('.' + this.button_class_edit);
        var reset_button = template.find('.' + this.button_class_reset);
        var delete_button = template.find('.' + this.button_class_delete);

        template.find('.' + this.class_editable_field).prop('disabled', !edit_mode);
        edit_button.text(edit_mode ? 'Save' : 'Edit');
        delete_button.text(deleted ? 'Restore' : 'Delete');

        edit_button.hide();
        reset_button.hide();
        delete_button.hide();

        if (edit_mode) {
            reset_button.show();
            edit_button.show();
        } else {
            if (modified) {
                if (!new_item) {
                    reset_button.show();
                } else {
                    delete_button.show();
                }
            } else {
                delete_button.show();
            }

            if (!deleted) {
                edit_button.show();
            }
        }

        template.attr('data-id', id);

        this.fillRowFields(template, item);

        return template;
    };

    ListableItemsAdmin.prototype.fillRowFields = function (template, item) {
        template.find('.' + this.class_editable_field).each(function () {
            var field = $(this);
            var type = field.attr('type');
            var name = field.attr('data-name');
            //TODO: Add lodash 'has' for deeply complex objects, once lodash exists.
            var value = (name in item ? item[name] : null);

            if (type === 'text') {
                field.val(value);
            } else if (type === 'checkbox') {
                field.attr('checked', value);
            } else {
                console.log('Unsupported field type ' + type);
            }
        });
    };

    ListableItemsAdmin.prototype.checkModified = function () {
        if (Object.keys(this.modified_items).length !== 0 || this.deleted_items_ids.length > 0) {
            this.button_save.show();
            this.button_cancel.show();
        } else {
            this.button_save.hide();
            this.button_cancel.hide();
        }
    };

    ListableItemsAdmin.prototype.checkPageNavigation = function () {
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

    ListableItemsAdmin.prototype.getFieldsFromTemplate = function () {
        var template = $($.parseHTML(this.html_template.html()));
        var fields = [];
        template.find('.' + this.class_editable_field).each(function () {
            fields.push($(this).attr('data-name'));
        });
        this.editable_field_names = fields;
    };

    var items_admin = new ListableItemsAdmin();

    items_admin.getFieldsFromTemplate();

    items_admin.loadPageCount();

    items_admin.changePage(items_admin.page);

    items_admin.button_create.click(function () {
        items_admin.createNew();
    });
    items_admin.button_next.click(function () {
        items_admin.changePage(items_admin.page + 1)
    });
    items_admin.button_previous.click(function () {
        items_admin.changePage(items_admin.page - 1)
    });
    items_admin.button_save.click(function () {
        items_admin.save();
    });
    items_admin.button_cancel.click(function () {
        items_admin.resetAll();
    });

    return items_admin;
});
