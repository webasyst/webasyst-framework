<?php

return array(
    'source' => array(
        'title' => /*_w*/('Time'),
        'control_type' => waHtmlControl::SELECT,
        'value' => 'local',
        'options' => array(
            array(
                'value'       => 'local',
                'title'       => /*_w*/('Local'),
            ),
            array(
                'value'       => 'server',
                'title'       => /*_w*/('Server'),
            ),
            array(
                'value'       => '-12',
                'title'       => 'UTC-12:00',
            ),
            array(
                'value'       => '-11',
                'title'       => 'UTC-11:00',
            ),
            array(
                'value'       => '-10',
                'title'       => 'UTC-10:00',
            ),
            array(
                'value'       => '-9.5',
                'title'       => 'UTC-09:30',
            ),
            array(
                'value'       => '-9',
                'title'       => 'UTC-09:00',
            ),
            array(
                'value'       => '-8',
                'title'       => 'UTC-08:00',
            ),
            array(
                'value'       => '-7',
                'title'       => 'UTC-07:00',
            ),
            array(
                'value'       => '-6',
                'title'       => 'UTC-06:00',
            ),
            array(
                'value'       => '-5',
                'title'       => 'UTC-05:00',
            ),
            array(
                'value'       => '-4.5',
                'title'       => 'UTC-04:30',
            ),
            array(
                'value'       => '-4',
                'title'       => 'UTC-04:00',
            ),
            array(
                'value'       => '-3',
                'title'       => 'UTC-03:00',
            ),
            array(
                'value'       => '-2',
                'title'       => 'UTC-02:00',
            ),
            array(
                'value'       => '-1',
                'title'       => 'UTC-01:00',
            ),
            array(
                'value'       => '0',
                'title'       => 'UTC+00:00',
            ),
            array(
                'value'       => '1',
                'title'       => 'UTC+01:00',
            ),
            array(
                'value'       => '2',
                'title'       => 'UTC+02:00',
            ),
            array(
                'value'       => '3',
                'title'       => 'UTC+03:00',
            ),
            array(
                'value'       => '3.5',
                'title'       => 'UTC+03:30',
            ),
            array(
                'value'       => '4',
                'title'       => 'UTC+04:00',
            ),
            array(
                'value'       => '4.5',
                'title'       => 'UTC+04:30',
            ),
            array(
                'value'       => '5',
                'title'       => 'UTC+05:00',
            ),
            array(
                'value'       => '5.5',
                'title'       => 'UTC+05:30',
            ),
            array(
                'value'       => '5.75',
                'title'       => 'UTC+05:45',
            ),
            array(
                'value'       => '6',
                'title'       => 'UTC+06:00',
            ),
            array(
                'value'       => '6.5',
                'title'       => 'UTC+06:30',
            ),
            array(
                'value'       => '7',
                'title'       => 'UTC+07:00',
            ),
            array(
                'value'       => '8',
                'title'       => 'UTC+08:00',
            ),
            array(
                'value'       => '8.75',
                'title'       => 'UTC+08:45',
            ),
            array(
                'value'       => '9',
                'title'       => 'UTC+09:00',
            ),
            array(
                'value'       => '9.5',
                'title'       => 'UTC+09:30',
            ),
            array(
                'value'       => '10',
                'title'       => 'UTC+10:00',
            ),
            array(
                'value'       => '10.5',
                'title'       => 'UTC+10:30',
            ),
            array(
                'value'       => '11',
                'title'       => 'UTC+11:00',
            ),
            array(
                'value'       => '11.5',
                'title'       => 'UTC+11:30',
            ),
            array(
                'value'       => '12',
                'title'       => 'UTC+12:00',
            ),
            array(
                'value'       => '12.75',
                'title'       => 'UTC+12:45',
            ),
            array(
                'value'       => '13',
                'title'       => 'UTC+13:00',
            ),
            array(
                'value'       => '14',
                'title'       => 'UTC+14:00',
            ),
        )
    ),

    'type' => array(
	    'title' => /*_w*/('Type'),
	    'control_type' => waHtmlControl::SELECT,
	    'options' => array(
		    array(
			    'value'       => 'round',
			    'title'       => /*_w*/('Round'),
		    ),
		    array(
			    'value'       => 'electronic',
			    'title'       => /*_w*/('Electronic'),
		    ),
	    )
    ),

    'town' => array(
        'title' => /*_w*/('Caption'),
        'control_type' => waHtmlControl::INPUT,
        'options' => array(
            array(
                'value'       => '',
                'title'       => /*_w*/('City'),
            ),
        )
    ),

    'format' => array(
        'title' => /*_w*/('Electronic format'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value'       => '24',
                'title'       => /*_w*/('Electronic 24 hours'),
            ),
            array(
                'value'       => '12',
                'title'       => /*_w*/('Electronic AM/PM'),
            ),
        )
    ),
);