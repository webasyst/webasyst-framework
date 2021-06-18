// === Structures ===
var PhotoStream = (function() {
    var constructor = function(options) {
        this._options = options || {};
        this._onClear = this._options.onClear || function() {};
        this._photo_stream = [];
    };
    $.extend(constructor.prototype, {
        getById: function(id) {
            for (var i = 0, p = this._photo_stream[0], n = this._photo_stream.length; i < n; p = this._photo_stream[++i]) {
                if (p && p.id == id) {
                    return p;
                }
            }
            return null;
        },
        updateById: function(id, photo, push_new) {
            var p = this.getById(id);
            if (p) {
                $.extend(p, photo);
                return p;
            }
            push_new = push_new || false;
            if (push_new) {
                this._photo_stream.push(photo);
            }
            return photo;
        },
        deleteById: function(id) {
            if (!$.isArray(id)) {
                id = [id];
            }
            for (var ids = id, i = 0, n = ids.length, id = ids[0]; i < n; id = ids[++i]) {
                var p = this.getById(id);
                if (p && p.index) {
                    delete this._photo_stream[p.index];
                }
            }
            var new_photo_stream = [],
                index = 0;
            for (var i = 0, n = this._photo_stream.length, p = this._photo_stream[0]; i < n; p = this._photo_stream[++i]) {
                if (p) {
                    p.index = index++;
                    new_photo_stream.push(p);
                }
            }
            this._photo_stream = new_photo_stream;
            return this;
        },
        replace: function(old_photo, new_photo) {
            var index = old_photo.index;
            this._photo_stream[index] = new_photo;
            new_photo.index = index;
            return this;
        },
        push: function(photo) {
            this._photo_stream.push(photo);
            photo.index = this._photo_stream.length - 1;
            return this;
        },
        getNext: function(photo) {
            photo = photo || this.current;
            if (typeof photo != 'object') {
                photo = this.getById(photo);
            }
            if (!photo) {
                return null;
            }
            var index = photo.index + 1;
            return this._photo_stream[index] ? this._photo_stream[index] : null;
        },
        getPrev: function(photo) {
            photo = photo || this.current;
            if (typeof photo != 'object') {
                photo = this.getById(photo);
            }
            if (!photo) {
                return null;
            }
            var index = photo.index - 1;
            return this._photo_stream[index] ? this._photo_stream[index] : null;
        },
        setCurrent: function(photo) {
            if (!photo.index) {
                photo = this.getById(photo.id);
                if (!photo || (typeof photo.index !== 'number' && !photo.index)) {
                    throw new Error("This photo is not in stream");
                }
            }
            this.current = photo;
            return this;
        },
        setCurrentById: function(id)
        {
            var photo = this.getById(id);
            if (typeof photo === 'object' && photo) {
                this.setCurrent(photo);
            }
            return this;
        },
        getCurrent: function() {
            return this.current;
        },
        getCurrentId: function()
        {
            if (typeof this.current === 'object' && this.current) {
                return this.current.id;
            }
            return null;
        },
        set: function(photos) {
            this._photo_stream = [];
            this.append(photos);
            if (!this.isEmpty()) {
                this.setCurrent(this.getFirst());
            }
            return this;
        },
        append: function(photos) {
            for (var i = 0, p = photos[i], j = this._photo_stream.length, n = photos.length; i < n; p = photos[++i], ++j) {
                p.index = j;
                this._photo_stream.push(p);
            }
            return this;
        },
        prepend: function(photos) {
            var existed = this._photo_stream || [];
            this._photo_stream = [];
            for (var i = 0, p = photos[i], n = photos.length; i < n; p = photos[++i]) {
                p.index = i;
                this._photo_stream.push(p);
            }
            this.append(existed);
            return this;
        },
        clear: function() {
            this._photo_stream = [];
            this._onClear();
            return this;
        },
        length: function() {
            return this._photo_stream.length;
        },
        getAll: function() {
            return this._photo_stream;
        },
        getFirst: function() {
            return this._photo_stream[0];
        },
        getLast: function() {
            return this._photo_stream[this._photo_stream.length - 1];
        },
        isFirst: function(photo) {
            return photo && photo.index == 0;
        },
        isLast: function(photo) {
            return photo && photo.index == this._photo_stream.length - 1;
        },
        isEmpty: function() {
            return !this._photo_stream.length;
        },
        getCurrentIndex: function() {
            return this.current.index;
        },
        slice: function(start, end) {
            return this._photo_stream.slice(start, end);
        },
        /**
         * Move item(s) inside stream to place just before item with id=before_id
         * @param number|array id
         * @param number|null before_id If null or omitted than item(s) move to the end of stream
         */
        move: function(id, before_id) {
            var photos = [],
                photo,
                photo_stream = this._photo_stream;
            if (!$.isArray(id)) {
                photo = this.getById(id);
                //this._photo_stream.splice(photo.index, 1);
                delete photo_stream[photo.index];
                photos.push(photo);
            } else {
                for (var i = 0, n = id.length; i < n; ++i) {
                    photo = this.getById(id[i]);
                    //this._photo_stream.splice(photo.index, 1);
                    delete photo_stream[photo.index];
                    photos.push(photo);
                }
            }
            if (photos.length) {
                var place = 0;
                if (typeof before_id === 'undefined' || before_id === null) {
                    place = this._photo_stream.length;
                } else {
                    var photo = this.getById(before_id);
                    place = photo ? photo.index : 0;
                }
                
                Array.prototype.splice.apply(photo_stream, [place, 0].concat(photos));
                // clear spaces ('undefined' after delete operator) and reindex
                this.clear();
                var index = 0;
                for (var i = 0, n = photo_stream.length; i < n; ++i) {
                    var photo = photo_stream[i];
                    if (typeof photo === 'object' && photo) {
                        photo.index = index++;
                        this._photo_stream.push(photo);
                    }
                }
            }
        }
    });
    return constructor;
})();

// === functions ===
Date.parseISO = function (string) {
    var tried = Date.parse(string);
    if (!isNaN(tried)) {
        return tried;
    }
    var regexp = "([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})";
    var d = string.match(new RegExp(regexp));

    var date = new Date(d[1], 0, 1);

    if (d[2]) { date.setMonth(d[2] - 1); }
    if (d[3]) { date.setDate(d[3]); }
    if (d[4]) { date.setHours(d[4]); }
    if (d[5]) { date.setMinutes(d[5]); }
    if (d[6]) { date.setSeconds(d[6]); }

    return +date;
};

/**
 * Replace old src with new src in img tag with or not preloading. Also taking into account competition problem
 * 
 * @param jquery object img
 * @param string new_src
 * @param mixed fn. Optinality. 
 *     If parameter is null than just change src (without preloading). 
 *     If fn is function than it is callback after src changed (with preloading)
 *     If omitted (undefined) - with preloading
 * @param string namespace. Optionality. 
 *     Need for solving competition problem. Render only image of last calling of this namespace. 
 *     If omitted try to use id of tag or generate random namespace 
 */
function replaceImg (img, new_src, fn, namespace) {
    namespace = namespace || img.attr('id') || ('' + Math.random()).slice(2);
    replaceImg.loading_map = replaceImg.loading_map || {};
    replaceImg.loading_map[namespace] = new_src;
    img.unbind('load');
    if (fn === null) {
        img.attr('src', new_src);
    } else {
        $('<img>').attr('src', new_src).load(function() {
            // setTimeout need for fix FF "blink" problem with image rendering
            setTimeout(function() {
                // render img only of last calling of function for this namespace
                if (replaceImg.loading_map[namespace] == new_src) {
                    img.attr('src', new_src);
                    if (typeof fn == 'function') {
                        fn.call(img);
                    }
                }
                $(this).remove();
            }, 100);
        });
    }
    return namespace;
};

String.prototype.truncate = function(length, strip_tags) {
    strip_tags = typeof strip_tags === 'undefined' ? true : strip_tags;
    var str = '';
    if (strip_tags) {
        str = this.replace(/<\/?[\s\S]+?\/?>/g, '');
    }
    if (str.length > length - 3 && length > 3) {
        str = str.substr(0, length - 3) + '...';
    }
    return str;
};

/**
 * Parsing size-code (e.g. 500x400, 500, 96x96, 200x0) into key-value object with info about this size
 *
 * @see Server-side has the same function with the same realization
 * @param String size
 * @returns Object
 */
function parseSize(size)
{
    var type = 'unknown',
        ar_size = (''+size).split('x'),
        width = ar_size[0] && ar_size[0] != 0 ? ar_size[0] : null,
        height = ar_size[1] && ar_size[1] != 0 ? ar_size[1] : null;

    if (ar_size.length == 1) {
        type = 'max';
        height = width;
    } else {
        if (width == height) { // crop
            type = 'crop';
        } else {
            if (width && height) {
                type = 'rectangle';
            } else if (width === null) {
                type = 'height';
            } else if (height === null) {
                type = 'width';
            }
        }
    }
    return {
        type: type,
        width: width,
        height: height
    };
}

/**
 * Calculate real size of photo thumbnail
 *
 * @see Server-side has the same function with the same realization
 * @param Object photo Key-value object with photo info
 * @param String|Object size string size-code or key-value object returned by parseSize
 * @returns Object Key-value object with width and height values
 */
function getRealSizesOfThumb(photo, size)
{
    var photo_width = parseInt(photo.width, 10),   // typecast need for correct comparison
        photo_height = parseInt(photo.height, 10),
        rate = photo_width/photo_height,
        revert_rate = photo_height/photo_width,
        size_info;
    if (isNaN(rate) || isNaN(revert_rate)) {
        return null;
    }
    if (typeof size !== 'object') {
        size_info = parseSize(size);
    } else {
        size_info = size;
    }

    var type = size_info.type,
        width = size_info.width,
        height = size_info.height,
        w, h;
    switch(type) {
        case 'max':
            if (photo_width > photo_height) {
                w = width;
                h = revert_rate*w;
            } else {
                h = width;
                w = rate*h;
            }
            break;
        case 'crop':
            w = h = width;
            break;
        case 'rectangle':
            w = width;
            h = height;
            break;
        case 'width':
            w = width;
            h = revert_rate*w;
          break;
        case 'height':
            h = height;
            w = rate*h;
            break;
        default:
            w = h = null;
            break;
    }
    w = Math.round(w);
    h = Math.round(h);
    if (photo_width < w && photo_height < h) {
        return {
            width: photo.width,
            height: photo.height
        };
    }
    return {
        width: w,
        height: h
    };
}