define(['jquery', './tagRepository.js'], function ($, tagRepo) {
    var tag = function () {
        this.repository = tagRepo;
    };

    tag.prototype.setActive = function (id, active, success, error) {
        return this.repository.save({active: active, id: id}, success, error)
    };

    return new tag();
});
