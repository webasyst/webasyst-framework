$.waui = {
    getHTML: function (c) {
        c = c.replace(/-/, '_');
        if (this[c + 'HTML']) {
            return this[c + 'HTML']();
        }
    },
    preview: function () {
        $("#wa-class-preview").contents().find('#wa-iframe-preview').html($("#wa-class-html").val());
        $("#wa-class-preview").contents().find('#wa-iframe-preview a').click(function () {
            return false
        });
    }
}

$(document).ready(function () {
   $("#wa-class-preview").contents().find('head').append('<link href="/wa-content/css/wa/wa-1.3.css" rel="stylesheet" type="text/css" />');
   $("#wa-class-preview").contents().find('head').append('<script type="text/javascript" src="/wa-content/js/jquery-wa/wa.dialog.js"></script>');
   $("#wa-class-preview").contents().find('body').html('<div id="wa-iframe-preview"></div>');
   $("#wa-ui-classes li a").click(function () {
       $("#wa-ui-classes li.selected").removeClass('selected');
       $(this).parent('li').addClass('selected');
       var id = $(this).attr('href').replace(/.*#/, '');
       $("#wa-class-description > div").hide();
       $("#wa-class-description > div#wa-class-desc-" + id).show();
       $("#wa-class-html").val($.waui.getHTML(id));
       if (id != 'dialog') $.waui.preview();
       else $("#wa-class-preview").contents().find('#wa-iframe-preview').html('<div class="block"><p>To view a dialog, click <b>Update</b> button above</p></div>');
   });

   $('ul.tabs.wa-class-settings li a').click( function() {
       $(this).parents('ul').find('li.selected').removeClass('selected');
       $(this).parent().addClass('selected');
       $("#wa-class-html").val($.waui.getHTML($("#wa-ui-classes li.selected a").attr('href').replace(/.*#/, '')));
       $.waui.preview();
       return false;
   });

   $("a.wa-ui-link").click(function () {
       $("#wa-ui-classes li a[href='" + $(this).attr('href') + "']").click();
   });


   var hash = location.hash;
   hash = hash.replace(/.*#/, '');
   if (hash) {
      $("#wa-ui-classes li a[href='#" + hash + "']").click();
   } else {
      $("#wa-ui-classes li:first a").click();
   }
});

                  /* ui class built-in examples content */

                   $.waui.menu_vHTML = function () {
                     var _v = $('ul#wa-menu-v-type li.selected').attr('id');
                     if (_v == 'wa-menu-v-type-f')
                      return '<div class="block" style="width:200px;">' + "\n" +
                      '<ul class="menu-v with-icons">' + "\n" +
                      '   <li>' + "\n" +
                      '      <a href="#" class="bold"><i class="icon16 add"></i>Upload photos</a>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li class="selected">' + "\n" +
                      '      <span class="count">273</span>' + "\n" +
                      '      <a href="#"><i class="icon16 picture"></i>All photos</a>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li>' + "\n" +
                      '      <span class="count">13</span>' + "\n" +
                      '      <a href="#"><i class="icon16 star"></i>Selected for publication</a>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li>' + "\n" +
                      '      <span class="count">10</span>' + "\n" +
                      '      <a href="#"><i class="icon16 trash"></i>Trash</a>' + "\n" +
                      '   </li>' + "\n" +
                      '</ul>' + "\n" +
                      '</div>';
                    else if (_v == 'wa-menu-v-type-h')
                     return '<div class="block hierarchical collapsible" style="width:200px;">' + "\n" +
                      '<ul class="menu-v with-icons">' + "\n" +
                      '   <li>' + "\n" +
                      '      <span class="count">85</span>' + "\n" +
                      '      <i class="icon16 darr overhanging"></i>' + "\n" +
                      '      <a href="#"><i class="icon16 pictures"></i>Work</a>' + "\n" +
                      '      <ul class="menu-v with-icons">' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">30</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>★★★★+</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">17</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>(to delete)</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">30</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>2012</a>' + "\n" +
                      '         </li>' + "\n" +

                      '      </ul>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li>' + "\n" +
                      '      <span class="count">440</span>' + "\n" +
                      '      <i class="icon16 darr overhanging"></i>' + "\n" +
                      '      <a href="#"><i class="icon16 star"></i>Selected for publication</a>' + "\n" +
                      '      <ul class="menu-v with-icons">' + "\n" +
                      '         <li>' + "\n" +
                      '            <i class="icon16 darr overhanging"></i>' + "\n" +
                      '            <span class="count">251</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Travel</a>' + "\n" +
                      '            <ul class="menu-v with-icons">' + "\n" +
                      '               <li>' + "\n" +
                      '                  <span class="count">125</span>' + "\n" +
                      '                  <a href="#"><i class="icon16 picture"></i>Europe</a>' + "\n" +
                      '               </li>' + "\n" +
                      '               <li>' + "\n" +
                      '                  <span class="count">90</span>' + "\n" +
                      '                  <a href="#"><i class="icon16 picture"></i>Asia</a>' + "\n" +
                      '               </li>' + "\n" +
                      '            </ul>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <i class="icon16 rarr overhanging"></i>' + "\n" +
                      '            <span class="count">17</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Portraits</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <i class="icon16 rarr overhanging"></i>' + "\n" +
                      '            <span class="count">80</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Friends</a>' + "\n" +
                      '         </li>' + "\n" +

                      '      </ul>' + "\n" +
                      '   </li>' + "\n" +
                      '</ul>' + "\n" +
                      '</div>';
                    else if (_v == 'wa-menu-v-type-d')
                      return '<div class="block" style="width:200px;">' + "\n" +
                      '<ul class="menu-v with-icons dropdown">' + "\n" +
                      '   <li>' + "\n" +
                      '      <span class="count"><i class="icon10 rarr overhanging"></i></span>' + "\n" +
                      '      <a href="#"><i class="icon16 pictures"></i>Work</a>' + "\n" +
                      '      <ul class="menu-v with-icons">' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">30</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>★★★★+</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">17</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>(to delete)</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">30</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>2012</a>' + "\n" +
                      '         </li>' + "\n" +

                      '      </ul>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li>' + "\n" +
                      '      <span class="count"><i class="icon10 rarr overhanging"></i></span>' + "\n" +
                      '      <a href="#"><i class="icon16 star"></i>Selected for publication</a>' + "\n" +
                      '      <ul class="menu-v with-icons">' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count"><i class="icon10 rarr overhanging"></i></span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Travel</a>' + "\n" +
                      '            <ul class="menu-v with-icons">' + "\n" +
                      '               <li>' + "\n" +
                      '                  <span class="count">125</span>' + "\n" +
                      '                  <a href="#"><i class="icon16 picture"></i>Europe</a>' + "\n" +
                      '               </li>' + "\n" +
                      '               <li>' + "\n" +
                      '                  <span class="count">90</span>' + "\n" +
                      '                  <a href="#"><i class="icon16 picture"></i>Asia</a>' + "\n" +
                      '               </li>' + "\n" +
                      '            </ul>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">17</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Portraits</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">80</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Friends</a>' + "\n" +
                      '         </li>' + "\n" +

                      '      </ul>' + "\n" +
                      '   </li>' + "\n" +
                      '</ul>' + "\n" +
                      '</div>';
                   }


                  $.waui.menu_hHTML = function () {
                     var _v = $('ul#wa-menu-h-type li.selected').attr('id');
                     if (_v == 'wa-menu-h-type-f')
                      return '<div class="block">' + "\n" +
                      '<ul class="menu-h with-icons">' + "\n" +
                      '   <li>' + "\n" +
                      '      <a href="#" class="bold"><i class="icon16 add"></i>Upload photos</a>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li class="selected">' + "\n" +
                      '      <a href="#"><i class="icon16 picture"></i>All photos</a>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li>' + "\n" +
                      '      <a href="#"><i class="icon16 star"></i>Favorites</a>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li>' + "\n" +
                      '      <a href="#"><i class="icon16 trash"></i>Trash</a>' + "\n" +
                      '   </li>' + "\n" +
                      '</ul>' + "\n" +
                      '</div>';
                    else if (_v == 'wa-menu-h-type-d')
                      return '<div class="block">' + "\n" +
                      '<ul class="menu-h dropdown">' + "\n" +
                      '   <li style="width: 120px;">' + "\n" +
                      '      <a href="#"><i class="icon16 pictures"></i>Work<i class="icon10 darr"></i></a>' + "\n" +
                      '      <ul class="menu-v with-icons">' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">30</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>★★★★+</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">17</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>(to delete)</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">30</span>' + "\n" +
                      '            <a href="#"><i class="icon16 funnel"></i>2012</a>' + "\n" +
                      '         </li>' + "\n" +

                      '      </ul>' + "\n" +
                      '   </li>' + "\n" +
                      '   <li style="width: 130px;">' + "\n" +
                      '      <a href="#"><i class="icon16 star"></i>Favorites<i class="icon10 darr"></i></a>' + "\n" +
                      '      <ul class="menu-v with-icons drop">' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count"><i class="icon10 rarr overhanging"></i></span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Travel</a>' + "\n" +
                      '            <ul class="menu-v with-icons">' + "\n" +
                      '               <li>' + "\n" +
                      '                  <span class="count">125</span>' + "\n" +
                      '                  <a href="#"><i class="icon16 picture"></i>Europe</a>' + "\n" +
                      '               </li>' + "\n" +
                      '               <li>' + "\n" +
                      '                  <span class="count">90</span>' + "\n" +
                      '                  <a href="#"><i class="icon16 picture"></i>Asia</a>' + "\n" +
                      '               </li>' + "\n" +
                      '            </ul>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">17</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Portraits</a>' + "\n" +
                      '         </li>' + "\n" +
                      '         <li>' + "\n" +
                      '            <span class="count">80</span>' + "\n" +
                      '            <a href="#"><i class="icon16 pictures"></i>Friends</a>' + "\n" +
                      '         </li>' + "\n" +

                      '      </ul>' + "\n" +
                      '   </li>' + "\n" +
                      '</ul>' + "\n" +
                      '</div>';

                  }

                  $.waui.tabsHTML = function () {
                      return '<div class="block">' + "\n" +
                      '   <ul class="tabs">' + "\n" +
                      '      <li><a href="#">привет</a></li>' + "\n" +
                      '      <li class="selected"><a href="#">hello</a></li>' + "\n" +
                      '      <li><a href="#">hola</a></li>' + "\n" +
                      '      <li><a href="#">bonjour</a></li>' + "\n" +
                      '      <li class="no-tab"><a href="#">(source)</a></li>' + "\n" +
                      '   </ul>' + "\n" +
                      '   <div class="tab-content ">' + "\n" +
                      '      <div class="block">' + "\n" +
                      '         Hello, with that spelling, was used in publications as early as 1833. The word was extensively used in literature by the 1860s.' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n" +
                      '</div>';
                  }



                  $.waui.zebraHTML = function () {
                    var _v = $('ul#wa-zebra-type li.selected').attr('id');
                      if (_v == 'wa-zebra-type-ul')
                      return '<div class="block">' + "\n" +
                      '   <ul class="zebra">' + "\n" +
                      '      <li>привет</li>' + "\n" +
                      '      <li>hello</li>' + "\n" +
                      '      <li>hola</li>' + "\n" +
                      '      <li>bonjour</li>' + "\n" +
                      '   </ul>' + "\n" +
                      '</div>';
                      else
                      return '<div class="block">' + "\n" +
                      '   <table class="zebra">' + "\n" +
                      '      <tr>' + "\n" +
                      '         <td>привет</td>' + "\n" +
                      '      </tr>' + "\n" +
                      '      <tr>' + "\n" +
                      '         <td>hello</td>' + "\n" +
                      '      </tr>' + "\n" +
                      '      <tr>' + "\n" +
                      '         <td>hola</td>' + "\n" +
                      '      </tr>' + "\n" +
                      '      <tr>' + "\n" +
                      '         <td>bonjour</td>' + "\n" +
                      '      </tr>' + "\n" +
                      '   </table>' + "\n" +
                      '</div>';
                  }



                  $.waui.thumbsHTML = function () {
                      return '<div class="block">' + "\n" +
                      '   <ul class="thumbs li100px">' + "\n" +
                      '      <li>' + "\n" +
                      '         <div>' + "\n" +
                      '            <a href="#"><img src="/wa-content/img/userpic96.jpg"></a>' + "\n" +
                      '         </div>' + "\n" +
                      '         <div>' + "\n" +
                      '            <input type="checkbox" />' + "\n" +
                      '            <a href="#">Jimmy W. Clark</a>' + "\n" +
                      '         </div>' + "\n" +
                      '      </li>' + "\n" +
                      '      <li class="highlighted">' + "\n" +
                      '         <div>' + "\n" +
                      '            <img src="/wa-content/img/userpic96.jpg">' + "\n" +
                      '         </div>' + "\n" +
                      '         <div>' + "\n" +
                      '            You do not have permissions to view this user profile' + "\n" +
                      '         </div>' + "\n" +
                      '      </li>' + "\n" +
                      '      <li class="selected">' + "\n" +
                      '         <div>' + "\n" +
                      '            <a href="#"><img src="/wa-content/img/userpic96.jpg"></a>' + "\n" +
                      '         </div>' + "\n" +
                      '         <div>' + "\n" +
                      '            <input type="checkbox" checked />' + "\n" +
                      '            <a href="#">Partner</a>' + "\n" +
                      '         </div>' + "\n" +
                      '      </li>' + "\n" +
                      '   </ul>' + "\n" +
                      '</div>';
                  }




                  $.waui.sidebarHTML = function () {
                      return '<div class="sidebar left200px" style="background: #ffa;">' + "\n" +
                      '   <div class="block double-padded">' + "\n" +
                      '      <h3>Left sidebar 200px</h3>' + "\n" +
                      '      Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lobortis porta mi, eget vulputate orci viverra at. Mauris blandit risus consequat mi consequat rhoncus.' + "\n" +
                      '   </div>' + "\n" +
                      '</div>' + "\n" +
                      '<div class="sidebar right100px" style="background: #faf;">' + "\n" +
                      '   <div class="block double-padded">' + "\n" +
                      '      <h3>Right sidebar 100px</h3>' + "\n" +
                      '      Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lobortis porta mi, eget vulputate orci viverra at. Mauris blandit risus consequat mi consequat rhoncus.' + "\n" +
                      '   </div>' + "\n" +
                      '</div>' + "\n" +
                      '<div class="content left200px right100px" style="background: #aff;">' + "\n" +
                      '   <div class="block double-padded">' + "\n" +
                      '      <h1>Content</h1>' + "\n" +
                      '      Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lobortis porta mi, eget vulputate orci viverra at. Mauris blandit risus consequat mi consequat rhoncus. Aenean ac aliquet lorem. Duis mauris arcu, iaculis sit amet venenatis eu, dapibus et erat. Donec eros augue, gravida vel pretium id, varius eu augue. In lorem arcu, ullamcorper sit amet adipiscing vel, facilisis a sapien. Nunc elit justo, rhoncus nec rutrum vel, pellentesque id purus. Fusce interdum, diam eu commodo accumsan, turpis libero euismod libero, at posuere ante diam non augue.' + "\n" +
                      '   </div>' + "\n" +
                      '</div>';
                  }


                  $.waui.fieldsHTML = function () {
                     var _v = $('ul#wa-fields-type li.selected').attr('id');
                     if (_v == 'wa-fields-type-view')
                      return '<div class="block fields">' + "\n\n" +

                      '   <div class="field-group">' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">First name</div>' + "\n" +
                      '         <div class="value"><strong>Jimmy</strong></div>' + "\n" +
                      '      </div>' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">Last name</div>' + "\n" +
                      '         <div class="value"><strong>Clark</strong></div>' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n\n" +


                     '   <div class="field-group">' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">Address</div>' + "\n" +
                      '         <div class="value">' + "\n" +
                      '            <img src="/wa-content/img/country/usa.gif" class="overhanging" />' + "\n" +
                      '            665 Dawson Drive<br />' + "\n" +
                      '            Newark, Delaware, 19713, USA<br />' + "\n" +
                      '         </div>' + "\n" +
                      '      </div>' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">Phone</div>' + "\n" +
                      '         <div class="value">1 800 550-13-12</div>' + "\n" +
                      '         <div class="value">+7 495 663-73-25</div>' + "\n" +
                      '      </div>' + "\n" +

                      '      <div class="field">' + "\n" +
                      '         <div class="name">Company</div>' + "\n" +
                      '         <div class="value">Webasyst</div>' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n\n" +

                      '</div>';
                    else if (_v == 'wa-fields-type-form')
                      return '<div class="block fields form">' + "\n\n" +

                      '   <div class="field-group">' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">First name</div>' + "\n" +
                      '         <div class="value"><input type="text" class="bold" value="Jimmy" /></div>' + "\n" +
                      '      </div>' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">Last name</div>' + "\n" +
                      '         <div class="value"><input type="text" class="bold" value="Clark" /></div>' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n\n" +


                     '   <div class="field-group">' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">Address</div>' + "\n" +
                      '         <div class="value">' + "\n" +
                      '            <textarea>665 Dawson Drive' + "\n" +
                      'Newark, Delaware, 19713</textarea>' + "\n" +
                      '         </div>' + "\n" +
                      '      </div>' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="name">Phone</div>' + "\n" +
                      '         <div class="value"><input type="text" value="1 800 550-13-12" /></div>' + "\n" +
                      '         <div class="value"><input type="text" value="+7 495 663-73-25" /></div>' + "\n" +
                      '      </div>' + "\n" +

                      '      <div class="field">' + "\n" +
                      '         <div class="name">Company</div>' + "\n" +
                      '         <div class="value"><select><option>Webasyst</option></select></div>' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n\n" +

                     '   <div class="field-group">' + "\n" +
                      '      <div class="field">' + "\n" +
                      '         <div class="value">' + "\n" +
                      '            <input type="button" class="button" value="Save" />' + "\n" +
                      '         </div>' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n\n" +

                      '</div>';

                  }





                  $.waui.icon16HTML = function () {
                      return '<div class="block">' + "\n\n" +
                      '   <a href="#"><i class="icon16 add"></i>Add</a><br /><br />' + "\n\n" +
                      '   Icons can be mixed with any text <i class="icon10 yes"></i><br /><br />' + "\n\n" +
                      '   <i class="icon16" style="background-image: url(\'http://p.yusukekamiyamane.com/icons/search/fugue/icons/guitar.png\')"></i> Use any custom 16x16 icons <i class="icon16" style="background-image: url(\'http://p.yusukekamiyamane.com/icons/search/fugue/icons/car.png\')"></i><br /><br />' + "\n\n" +
                      '   User rating: <i class="icon10 star"></i><i class="icon10 star"></i><i class="icon10 star"></i><i class="icon10 star"></i><i class="icon10 star-empty"></i> 4.0/5.0<br /><br />' + "\n\n" +
                      '   Loading... <i class="icon16 loading"></i>' + "\n\n" +
                      '</div>';
                  }



                  $.waui.dialogHTML = function () {
                    return '<div class="block"><p>To view a dialog click <b>Update</b> button above</p></div>\n\n' +
                      '<script type="text/javascript">\n' +
                      '$(\'<div><p>Enter your name: <input type="text" name="name"/></p><p class="hint">Close the dialog by clicking “Close” or hitting <strong>Esc</strong> key on your keyboard</p></div>\').waDialog({\n' +
                      '  \'buttons\': \'<input type="submit" value="Close" class="button" />\',\n' +
                      '  \'height\': \'100px\',\n' +
                      '  \'width\': \'300px\',\n' +
                      '  onSubmit: function (d) {\n' +
                      '       d.trigger(\'close\');\n' +
                      '       return false;\n' +
                      '  }\n' +
                      '});\n' +
                      '</script>\n';
                  }


                  $.waui.profileHTML = function () {
                      return '<div class="block">' + "\n" +
                      '   <div class="profile image96px">' + "\n" +
                      '      <div class="image">' + "\n" +
                      '         <img src="/wa-content/img/userpic96.jpg" />' + "\n" +
                      '      </div>' + "\n" +
                      '      <div class="details">' + "\n" +
                      '         <h2>Vladimir Tuporshin, Sr.</h2>' + "\n" +
                      '         <p>Vladimir Tuporshin, Sr. is the initial founder of the WebAsyst LLC. He is responsible for setting goals and targets in software development, and conducts overall management of the company. Position: director. Graduate of the Faculty of Physics at the Lomonosov Moscow State University. PhD of Physics and Mathematics. Goes in for sport and watercolor painting. Master of sports in decathlon.</p>' + "\n" +
                      '      </div>' + "\n" +
                      '   </div>' + "\n" +
                      '</div>';
                  }


                  $.waui.progressbarHTML = function () {
                      return '<div class="block">' + "\n" +
                      '   Installing Webasyst... <i class="icon16 loading"></i>' + "\n\n" +
                      '   <div class="progressbar blue">' + "\n" +
                      '     <div class="progressbar-outer">' + "\n" +
                      '       <div class="progressbar-inner" id="my-custom-progressbar-id" style="width: 37%;">' + "\n" +
                      '       </div>' + "\n" +
                      '     </div>' + "\n" +
                      '   </div>' + "\n" +
                      '   Unarchiving Stickies app...' + "\n\n" +
                      '   <div class="progressbar small yellow">' + "\n" +
                      '     <div class="progressbar-outer">' + "\n" +
                      '       <div class="progressbar-inner" id="my-custom-progressbar-id" style="width: 83%;">' + "\n" +
                      '       </div>' + "\n" +
                      '     </div>' + "\n" +
                      '   </div>' + "\n" +
                      '</div>';
                  }



                  $.waui.variousHTML = function () {
                      return '<div class="block">' + "\n" +
                      '   <p>' + "\n" +
                      '      Links can be underlined with either a <a href="#">solid line</a> or a <a href="#" class="inline-link"><b><i>dotted line</i></b></a>.' + "\n" +
                      '      <span class="hint">Dotted underlining is recommended for in-place links that perform local actions or show content somewhere on a page. Solid underlining is for links that open dialogs or redirect user to other web pages.</span>' + "\n" +
                      '   </p>' + "\n" +
                      '   <p>' + "\n" +
                      '      <input type="button" class="button green" value="Green buttons"> are great for saving data changes,' + "\n" +
                      '      <input type="button" class="button red" value="Red buttons"> are for deleting operations.' + "\n" +
                      '   </p>' + "\n" +
                      '   <div class="block double-padded" style="border: 1px solid #ccc;">' + "\n" +
                      '      For abstract blocks use div<strong>.block</strong> class combined with .not-padded or .double-padded subclasses.' + "\n" +
                      '   </div>' + "\n" +
                      '</div>';
                  }
