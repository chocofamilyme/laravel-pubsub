# Changelog
## 5.1.0
- Added ability to recording all incoming subscribe events to database. To turn on this functionality you need to set ```true``` in ```record_sub_events``` parameter in ```config/pubsub.php``` (by default ```false```).

## 6.0.0
- Remove class Amqp
- Added column exchange_type and headers
- External events are now sent via Broadcasting
- Added a console command to republish events - `php artisan event:republish` 

## 5.0.0
 - Laravel 8 support
 - Minimum PHP version is set to 7.4
 
## 4.1.0
- Added a non-blocking worker mode
 
