<?php
return [
	'language' => 'Deutsch',
	'parametrized' => 'the value of param "foo" is %foo%.',
	'with_colon_escape' => 'a percent sign %%foo',
	'callable' => function( array $params ) {
		return 'the value of param "foo" is ' . $params['foo'] . '.';
	},
	'invalid' => 123 // must be string or callable
];
