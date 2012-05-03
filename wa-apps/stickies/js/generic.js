var translate = {};
String.prototype.translate = function() {
	return translate[this]?translate[this]:this;
};
