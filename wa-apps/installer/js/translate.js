/**
 * @example
 * translate['Hello world'] = 'Bonjour tout le monde';
 * alert('Hello world'.translate());
 */
var translate = {};
String.prototype.translate = function() {
	return translate[this]?translate[this]:this;
};