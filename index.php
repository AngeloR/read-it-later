<?php

include('lib/Read_It_Later.php');

$ril = new Read_It_Later();

$details = array(
  'username' => '',
  'password' => '',
  'url' => urlencode('http://storify.com/knowtheory/papaya'),
  'title' => urlencode('Ancient world styling via bookmarklet')
);

$some = $ril->get('add',$details);

var_dump($some);
