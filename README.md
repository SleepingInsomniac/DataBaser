DataBaser
=========

PHP Mysqli convenience library loosely based on ActiveRecord

#To Connect:
    Dbaser\Base::setConnection(new Mysqli(...));

#Example:
```php
// example 'doctors' class any additional methods are optional.
class Doctor extends Dbaser\Model {

	// CREATE TABLE `doctors` (
	//   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	//   `first_name` varchar(64) DEFAULT NULL,
	//   `last_name` varchar(64) DEFAULT NULL,
	//   `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	//   PRIMARY KEY (`id`),
	//   KEY `last_name` (`last_name`)
	// ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

	function getName() {
		return "$this->first_name $this->last_name";
	}
	function setName($value) {
		$value = str_split(" ", $value);
		$this->first_name = $value[0];
		if (isset($value[1]))
			$this->last_name = $value[1];
	}
}

$doctor = Doctor::find(1); // find a doctor with primary key that equals 1
$doctor->name = "John Doe"; // set the doctor's name (note the getters and setters)
$doctor->save(); // update the database record;
```