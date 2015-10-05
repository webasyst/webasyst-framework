<?php

return array(
    'email' => array(
        'title'                  => /*_wp*/('Troll list'),
        'description'            =>/*_wp*/("A list defining troll search criteria (each line of the textarea defines a criteria). If any of the criteria match commentator's email, name or site URL, a troll face is shown.<br /><br />Example:<br /><em>koe9s@gmail.com<br />unwanteddomain.com<br />Alxs29<br />@troll.com</em>"),
        'value'                  => '',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
);
