define(["jquery"],function(t){return function(t){function e(n){if(i[n])return i[n].exports;var s=i[n]={i:n,l:!1,exports:{}};return t[n].call(s.exports,s,s.exports,e),s.l=!0,s.exports}var i={};return e.m=t,e.c=i,e.i=function(t){return t},e.d=function(t,i,n){e.o(t,i)||Object.defineProperty(t,i,{configurable:!1,enumerable:!0,get:n})},e.n=function(t){var i=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(i,"a",i),i},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="/intranet/thankyou/js/build/",e(e.s=1)}({"../../../node_modules/css-loader/index.js!../../../node_modules/sass-loader/lib/loader.js!./css/style.scss":function(t,e,i){e=t.exports=i("../../../node_modules/css-loader/lib/css-base.js")(),e.push([t.i,'.thank-you .thank-you-item {\n  list-style: none; }\n  .thank-you .thank-you-item:not(:first-of-type) {\n    margin-top: 20px; }\n\n.thank-you .thank-you-note {\n  background: #f5f5f5;\n  border-radius: 6px;\n  padding: 10px;\n  position: relative;\n  border: 1px solid #e9e9e9; }\n  .thank-you .thank-you-note hr {\n    margin: 8px 0;\n    border-color: #e7e7e7; }\n  .thank-you .thank-you-note:after, .thank-you .thank-you-note:before {\n    top: 100%;\n    content: " ";\n    border: solid transparent;\n    height: 0;\n    width: 0;\n    position: absolute;\n    pointer-events: none; }\n  .thank-you .thank-you-note:after {\n    border-color: transparent;\n    border-top-color: #f5f5f5;\n    border-width: 8px;\n    margin-left: -10px;\n    right: 10px; }\n  .thank-you .thank-you-note:before {\n    border-color: transparent;\n    border-top-color: #e9e9e9;\n    border-width: 10px;\n    margin-left: -8px;\n    right: 8px; }\n\n.thank-you .thank-you-meta {\n  margin-top: 15px;\n  text-align: right; }\n  .thank-you .thank-you-meta .author-photo {\n    width: 40px; }\n\n.thank-you .no-decoration:hover, .thank-you .no-decoration:focus {\n  text-decoration: none; }\n\n.thank-you .user-photo {\n  width: 26px;\n  margin-bottom: 3px; }\n\n.thank-you .thank-you-note a:nth-child(1) img {\n  margin-left: 8px; }\n\n.thank-you .js-like-component {\n  display: inline-block; }\n  .thank-you .js-like-component .liked.liked {\n    border-right: 1px solid #e7e7e7;\n    margin-right: 3px;\n    padding-right: 6px;\n    display: inline-block; }\n  .thank-you .js-like-component .glyphicons.glyphicons-thumbs-up {\n    width: 12px; }\n\n.thank-you .edit-tools {\n  display: block; }\n  .thank-you .edit-tools .edit-thanks {\n    border-right: 1px solid #e7e7e7;\n    margin-right: 3px;\n    padding-right: 6px; }\n  .thank-you .edit-tools .delete-thanks {\n    padding-left: 6px; }\n\n.panel .panel-heading .btn.pull-right.thank-you--button {\n  position: absolute;\n  right: 23px;\n  top: 14px; }\n\n.thank-you-link-inline {\n  text-align: right;\n  padding: 8px 10px 0 0;\n  font-weight: bold; }\n\n.tile-thank-you.grid-stack-item-6[data-gs-width="1"] .thank-title {\n  display: none; }\n\n@media only screen and (max-width: 768px) {\n  .tile-thank-you .thank-title {\n    display: none; } }\n\n.comments-toggle-wrapper.comments-border {\n  border-left: 1px solid #e7e7e7;\n  margin-left: 3px;\n  padding-left: 6px;\n  display: inline-block; }\n  .comments-toggle-wrapper.comments-border .glyphicons-comments.glyphicons {\n    color: #595959; }\n\n.comments {\n  display: none; }\n\n.js-listable-item-admin-container .listable-item-admin-new {\n  background: greenyellow; }\n\n.js-listable-item-admin-container .listable-item-admin-modified {\n  background: #00A6C7; }\n\n.js-listable-item-admin-container .listable-item-admin-deleted {\n  background: red; }\n\n.js-listable-item-admin-container .lia-heading {\n  text-align: center; }\n',""])},"../../../node_modules/css-loader/lib/css-base.js":function(t,e){t.exports=function(){var t=[];return t.toString=function(){for(var t=[],e=0;e<this.length;e++){var i=this[e];i[2]?t.push("@media "+i[2]+"{"+i[1]+"}"):t.push(i[1])}return t.join("")},t.i=function(e,i){"string"==typeof e&&(e=[[null,e,""]]);for(var n={},s=0;s<this.length;s++){var o=this[s][0];"number"==typeof o&&(n[o]=!0)}for(s=0;s<e.length;s++){var r=e[s];"number"==typeof r[0]&&n[r[0]]||(i&&!r[2]?r[2]=i:i&&(r[2]="("+r[2]+") and ("+i+")"),t.push(r))}},t}},"../../../node_modules/style-loader/addStyles.js":function(t,e,i){function n(t,e){for(var i=0;i<t.length;i++){var n=t[i],s=f[n.id];if(s){s.refs++;for(var o=0;o<s.parts.length;o++)s.parts[o](n.parts[o]);for(;o<n.parts.length;o++)s.parts.push(h(n.parts[o],e))}else{for(var r=[],o=0;o<n.parts.length;o++)r.push(h(n.parts[o],e));f[n.id]={id:n.id,refs:1,parts:r}}}}function s(t){for(var e=[],i={},n=0;n<t.length;n++){var s=t[n],o=s[0],r=s[1],a=s[2],l=s[3],d={css:r,media:a,sourceMap:l};i[o]?i[o].parts.push(d):e.push(i[o]={id:o,parts:[d]})}return e}function o(t,e){var i=_(t.insertInto);if(!i)throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");var n=g[g.length-1];if("top"===t.insertAt)n?n.nextSibling?i.insertBefore(e,n.nextSibling):i.appendChild(e):i.insertBefore(e,i.firstChild),g.push(e);else{if("bottom"!==t.insertAt)throw new Error("Invalid value for parameter 'insertAt'. Must be 'top' or 'bottom'.");i.appendChild(e)}}function r(t){t.parentNode.removeChild(t);var e=g.indexOf(t);e>=0&&g.splice(e,1)}function a(t){var e=document.createElement("style");return t.attrs.type="text/css",d(e,t.attrs),o(t,e),e}function l(t){var e=document.createElement("link");return t.attrs.type="text/css",t.attrs.rel="stylesheet",d(e,t.attrs),o(t,e),e}function d(t,e){Object.keys(e).forEach(function(i){t.setAttribute(i,e[i])})}function h(t,e){var i,n,s;if(e.singleton){var o=y++;i=b||(b=a(e)),n=c.bind(null,i,o,!1),s=c.bind(null,i,o,!0)}else t.sourceMap&&"function"==typeof URL&&"function"==typeof URL.createObjectURL&&"function"==typeof URL.revokeObjectURL&&"function"==typeof Blob&&"function"==typeof btoa?(i=l(e),n=p.bind(null,i,e),s=function(){r(i),i.href&&URL.revokeObjectURL(i.href)}):(i=a(e),n=u.bind(null,i),s=function(){r(i)});return n(t),function(e){if(e){if(e.css===t.css&&e.media===t.media&&e.sourceMap===t.sourceMap)return;n(t=e)}else s()}}function c(t,e,i,n){var s=i?"":n.css;if(t.styleSheet)t.styleSheet.cssText=x(e,s);else{var o=document.createTextNode(s),r=t.childNodes;r[e]&&t.removeChild(r[e]),r.length?t.insertBefore(o,r[e]):t.appendChild(o)}}function u(t,e){var i=e.css,n=e.media;if(n&&t.setAttribute("media",n),t.styleSheet)t.styleSheet.cssText=i;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(i))}}function p(t,e,i){var n=i.css,s=i.sourceMap,o=void 0===e.convertToAbsoluteUrls&&s;(e.convertToAbsoluteUrls||o)&&(n=v(n)),s&&(n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(s))))+" */");var r=new Blob([n],{type:"text/css"}),a=t.href;t.href=URL.createObjectURL(r),a&&URL.revokeObjectURL(a)}var f={},m=function(t){var e;return function(){return void 0===e&&(e=t.apply(this,arguments)),e}}(function(){return window&&document&&document.all&&!window.atob}),_=function(t){var e={};return function(i){return void 0===e[i]&&(e[i]=t.call(this,i)),e[i]}}(function(t){return document.querySelector(t)}),b=null,y=0,g=[],v=i("../../../node_modules/style-loader/fixUrls.js");t.exports=function(t,e){if("undefined"!=typeof DEBUG&&DEBUG&&"object"!=typeof document)throw new Error("The style-loader cannot be used in a non-browser environment");e=e||{},e.attrs="object"==typeof e.attrs?e.attrs:{},void 0===e.singleton&&(e.singleton=m()),void 0===e.insertInto&&(e.insertInto="head"),void 0===e.insertAt&&(e.insertAt="bottom");var i=s(t);return n(i,e),function(t){for(var o=[],r=0;r<i.length;r++){var a=i[r],l=f[a.id];l.refs--,o.push(l)}if(t){n(s(t),e)}for(var r=0;r<o.length;r++){var l=o[r];if(0===l.refs){for(var d=0;d<l.parts.length;d++)l.parts[d]();delete f[l.id]}}}};var x=function(){var t=[];return function(e,i){return t[e]=i,t.filter(Boolean).join("\n")}}()},"../../../node_modules/style-loader/fixUrls.js":function(t,e){t.exports=function(t){var e="undefined"!=typeof window&&window.location;if(!e)throw new Error("fixUrls requires window.location");if(!t||"string"!=typeof t)return t;var i=e.protocol+"//"+e.host,n=i+e.pathname.replace(/\/[^\/]*$/,"/");return t.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi,function(t,e){var s=e.trim().replace(/^"(.*)"$/,function(t,e){return e}).replace(/^'(.*)'$/,function(t,e){return e});if(/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/)/i.test(s))return t;var o;return o=0===s.indexOf("//")?s:0===s.indexOf("/")?i+s:n+s.replace(/^\.\//,""),"url("+JSON.stringify(o)+")"})}},"./css/style.scss":function(t,e,i){var n=i("../../../node_modules/css-loader/index.js!../../../node_modules/sass-loader/lib/loader.js!./css/style.scss");"string"==typeof n&&(n=[[t.i,n,""]]);i("../../../node_modules/style-loader/addStyles.js")(n,{});n.locals&&(t.exports=n.locals)},"./js/src/tags.js":function(t,e,i){var n,s;n=[i(0),i("./css/style.scss")],void 0!==(s=function(t){var e=function(){this.column_headings=[],this.new_item_key_preface="new-",this.row_new_class="listable-item-admin-new",this.row_modified_class="listable-item-admin-modified",this.row_deleted_class="listable-item-admin-deleted",this.class_editable_field="js-listable-item-admin-editable-field",this.class_editable_field_error="js-listable-item-admin-editable-field-error",this.row_class="js-listable-item-admin-row",this.button_create=t("#js-listable-item-admin-create"),this.button_class_edit="js-listable-item-admin-edit-button",this.button_class_reset="js-listable-item-admin-reset-button",this.button_class_delete="js-listable-item-admin-delete-button",this.button_next=t("#js-listable-item-admin-nav-next"),this.button_previous=t("#js-listable-item-admin-nav-previous"),this.button_save=t("#js-listable-item-admin-save"),this.button_cancel=t("#js-listable-item-admin-cancel"),this.limit=20,this.offset=0,this.page=1,this.page_count=1,this.html_template=t("#js-listable-item-admin-item-template"),this.template_heading=t("#js-lia-template-heading"),this.localised_edit=t("#js-lia-loc-edit").text(),this.localised_delete=t("#js-lia-loc-delete").text(),this.localised_save_edit=t("#js-lia-loc-save-edit").text(),this.localised_reset=t("#js-lia-loc-reset").text(),this.items_list=t("#js-listable-item-admin-list"),this.loaded_items={},this.unlocked_items={},this.modified_items={},this.item_errors={},this.displayed_ids=[],this.deleted_items_ids=[],this.new_item_ids=[],this.editable_field_names=[],this.new_offset=1,this.saveUrl="/api/thankyou/v2/tags",this.errors_banner=t("#js-listable-item-admin-errors"),this.no_results_banner=t("#js-listable-item-admin-no-results"),this.items_list.on("click","."+this.button_class_edit,function(){i.toggleEditMode(this.closest("."+i.row_class).getAttribute("data-id"))}),this.items_list.on("click","."+this.button_class_reset,function(){var t=this.closest("."+i.row_class).getAttribute("data-id");i.resetEdit(t),i.refreshDisplay()}),this.items_list.on("click","."+this.button_class_delete,function(){var t=this.closest("."+i.row_class).getAttribute("data-id");i.deleteItem(t)})};e.prototype.createNew=function(){var e=t(t.parseHTML(_.template(this.html_template.html())({}))),i={};this.fillItemFromRow(e,i);var n=this.new_item_key_preface+this.new_offset;this.new_offset++,this.loaded_items[n]=i,this.new_item_ids.unshift(n),this.displayed_ids.unshift(n),this.checkModified(),this.toggleEditMode(n)},e.prototype.changePage=function(t){var e=this;this.page=t,this.offset=(e.page-1)*this.limit,this.loadItems(this.limit,this.offset,function(t){e.displayed_ids=[];for(var i in e.new_item_ids)e.displayed_ids.push(e.new_item_ids[i]);for(var n in t)e.loaded_items[n]=t[n],e.displayed_ids.push(n);e.refreshDisplay()}),this.checkPageNavigation()},e.prototype.loadItems=function(e,i,n){t.ajax("/api/thankyou/v2/tags?limit="+e+"&offset="+i).done(function(t){"function"==typeof n&&n(t)})},e.prototype.fillItemFromRow=function(e,i){e.find("."+this.class_editable_field).each(function(){var e=t(this),n=e.attr("type"),s=e.attr("data-name"),o=null;"text"===n?(o=e.val(),e.val(o)):"checkbox"===n?o=e.prop("checked"):console.log("Unsupported field type "+n),i[s]=o})},e.prototype.toggleEditMode=function(t){t in this.unlocked_items?(this.updateItemFromForm(t),delete this.unlocked_items[t]):this.unlocked_items[t]=!0,this.refreshDisplay()},e.prototype.deleteItem=function(t){this.new_item_ids.includes(t)?this.forgetItem(t):this.deleted_items_ids.includes(t)||this.deleted_items_ids.push(t),this.refreshDisplay()},e.prototype.forgetItem=function(t){if(this.new_item_ids.includes(t)){var e=this.new_item_ids.indexOf(t);this.new_item_ids.splice(e,1)}if(this.displayed_ids.includes(t)){var i=this.displayed_ids.indexOf(t);this.displayed_ids.splice(i,1)}if(t in this.modified_items&&delete this.modified_items[t],t in this.loaded_items&&delete this.loaded_items[t],t in this.unlocked_items&&delete this.unlocked_items[t],this.deleted_items_ids.includes(t)){var n=this.deleted_items_ids.indexOf(t);this.deleted_items_ids.splice(n,1)}t in this.item_errors&&delete this.item_errors[t]},e.prototype.loadPageCount=function(){var e=this;t.ajax("/api/thankyou/v2/tags/total").done(function(t){e.page_count=Math.ceil(t/e.limit),e.changePage(e.page)})},e.prototype.getItem=function(t){return t in this.modified_items?this.modified_items[t]:this.loaded_items[t]},e.prototype.getModifiedItem=function(t){return t in this.modified_items||(this.modified_items[t]=JSON.parse(JSON.stringify(this.loaded_items[t]))),this.modified_items[t]},e.prototype.updateItemFromForm=function(e){var i=t("."+this.row_class+"[data-id="+e+"]"),n=this.getModifiedItem(e);this.fillItemFromRow(i,n),_.isEqual(n,this.loaded_items[e])&&this.resetEdit(e),this.refreshDisplay()},e.prototype.resetAll=function(){var t;for(t in this.new_item_ids)this.forgetItem(this.new_item_ids[t]);for(var e in this.modified_items)this.resetEdit(e);for(t in this.deleted_items_ids)this.resetEdit(this.deleted_items_ids[t]);this.refreshDisplay()},e.prototype.resetEdit=function(t){delete this.modified_items[t],delete this.item_errors[t],this.deleted_items_ids.includes(t)&&this.deleted_items_ids.splice(this.deleted_items_ids.indexOf(t)),this.new_item_ids.includes(t)&&this.getModifiedItem(t)},e.prototype.save=function(){var e=this;e.item_errors={};var i=e.new_item_ids,n=e.modified_items,s=e.saveUrl,o={created:{},modified:{},deleted:e.deleted_items_ids};for(var r in n)i.includes(r)?o.created[r]=n[r]:o.modified[r]=n[r];t.ajax({url:s,type:"POST",dataType:"json",contentType:"application/json",data:JSON.stringify(o)}).done(function(t){"errors"in t&&(e.item_errors=t.errors);for(var i in e.loaded_items)i in e.item_errors||e.forgetItem(i);e.changePage(1),e.loadPageCount()})},e.prototype.refreshDisplay=function(){this.items_list.empty();for(var e in this.column_headings){var i=t(t.parseHTML(_.template(this.template_heading.html())({})));i.append(this.column_headings[e]),this.items_list.append(i)}Object.keys(this.item_errors).length>0?this.errors_banner.show():this.errors_banner.hide();var n=this.displayed_ids;if(0===n.length)this.no_results_banner.show();else{this.no_results_banner.hide();for(var e in n){var s=this.getItem(n[e]);this.items_list.append(this.displayRow(n[e],s))}}this.checkModified()},e.prototype.displayRow=function(e,i){var n=this.new_item_ids.includes(e),s=e in this.unlocked_items,o=e in this.modified_items,r=this.deleted_items_ids.includes(e),a=t(t.parseHTML(_.template(this.html_template.html())({})));n?a.addClass(this.row_new_class):r?a.addClass(this.row_deleted_class):o&&a.addClass(this.row_modified_class);var l=a.find("."+this.button_class_edit),d=a.find("."+this.button_class_reset),h=a.find("."+this.button_class_delete);return a.find("."+this.class_editable_field).prop("disabled",!s),l.text(s?this.localised_save_edit:this.localised_edit),h.text(this.localised_delete),d.text(this.localised_reset),l.hide(),d.hide(),h.hide(),(!n&&(r||o)||n&&s)&&d.show(),r||l.show(),(n&&!s||!r&&!s&&!o)&&h.show(),a.attr("data-id",e),this.fillRowFields(e,a,i),a},e.prototype.fillRowFields=function(e,i,n){var s=this;i.find("."+this.class_editable_field).each(function(){var i=t(this),o=i.attr("type"),r=i.attr("data-name"),a=r in n?n[r]:null;if("text"===o?i.val(a):"checkbox"===o?i.attr("checked",a):console.log("Unsupported field type "+o),e in s.item_errors&&r in s.item_errors[e]){var l=i.next("."+s.class_editable_field_error);l.length>0&&l.text(s.item_errors[e][r])}})},e.prototype.checkModified=function(){0!==Object.keys(this.modified_items).length||this.deleted_items_ids.length>0?(this.button_save.show(),this.button_cancel.show()):(this.button_save.hide(),this.button_cancel.hide())},e.prototype.checkPageNavigation=function(){var t=this.page;1===t?this.button_previous.hide():this.button_previous.show(),t<this.page_count?this.button_next.show():this.button_next.hide()},e.prototype.getFieldsFromTemplate=function(){var e=t(t.parseHTML(this.html_template.html())),i=[];e.find("."+this.class_editable_field).each(function(){i.push(t(this).attr("data-name"))}),this.editable_field_names=i};var i=new e;return i.getFieldsFromTemplate(),i.loadPageCount(),i.button_create.click(function(){i.createNew()}),i.button_next.click(function(){i.changePage(i.page+1)}),i.button_previous.click(function(){i.changePage(i.page-1)}),i.button_save.click(function(){i.save()}),i.button_cancel.click(function(){i.resetAll()}),i}.apply(e,n))&&(t.exports=s)},0:function(e,i){e.exports=t},1:function(t,e,i){t.exports=i("./js/src/tags.js")}})});