<?php

return array(
    'source' => array(
        'title' => 'Time',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value'       => 'local',
                'title'       => 'Local',
            ),
            array(
                'value'       => 'server',
                'title'       => 'Server',
            ),
            array(
                'value'       => 'custom',
                'title'       => 'Custom',
            ),
        )
    ),

    'type' => array(
	    'title' => 'Type',
	    'control_type' => waHtmlControl::SELECT,
	    'options' => array(
		    array(
			    'value'       => 'round',
			    'title'       => 'Round',
		    ),
		    array(
			    'value'       => 'electronic',
			    'title'       => 'Electronic',
		    ),
	    )
    ),

    'lang' => array(
        'title' => 'Lang',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
	        array(
		        'value'       => 'ru',
		        'title'       => 'Русский',
	        ),
            array(
                'value'       => 'en',
                'title'       => 'English',
            ),
        )
    ),

    'town' => array(
        'title' => 'Caption',
        'control_type' => waHtmlControl::INPUT,
        'options' => array(
	        array(
		        'value'       => '',
		        'title'       => 'Город',
	        ),
            array(
                'value'       => '',
                'title'       => 'City',
            ),
        )
    ),

//    'format' => array(
//        'title' => 'Format',
//        'control_type' => waHtmlControl::SELECT,
//        'options' => array(
//            array(
//                'value'       => '24',
//                'title'       => '24',
//            ),
//            array(
//                'value'       => '12',
//                'title'       => '12',
//            ),
//        )
//    ),
);