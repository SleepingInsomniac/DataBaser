DataBaser
=========

PHP Mysqli convenience library loosely based on ActiveRecord

#To Connect:
    Dbaser\Base::setConnection(new Mysqli(...));

#Example:
For a simple model, all you need to do is define a class with the singular version of the database table name

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
```

Now perform some methods with that class...

```php
$doctor = Doctor::find(1); // find a doctor with primary key that equals 1
$doctor->name = "John Doe"; // set the doctor's name (note the getters and setters)
$doctor->save(); // update the database record;
```

#Many to many relations:
Define in the $manyToMany class array with the property name pointing to the class: (property => class)
```php
class Tool extends Dbaser\Model {
	static $manyToMany = ['doctors' => 'Doctor'];
}

class Doctor extends Dbaser\Model {
	static $manyToMany = ['tools' => 'Tool']
}
```

The relation between doctors and tools would be named doctors_tools in the database:
```sql
CREATE TABLE `doctors_tools` (
  `doctors` int(11) unsigned NOT NULL,
  `tools` int(11) unsigned NOT NULL,
  PRIMARY KEY (`doctors`,`tools`),
  KEY `tools` (`tools`),
  CONSTRAINT `fk1` FOREIGN KEY (`doctors`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk2` FOREIGN KEY (`tools`) REFERENCES `tools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

```php
$doc = Doctor::find(1);
$doc->tools; // returns collection of tools;
$scalpel = $doc->tools[0]; // first tool...
$hammer = new Tool(['name' => 'hammer']);
$hammer->save(); // create the record in the database and get any unset info
$doc->tools->push($hammer); // Doctor with id of 1 now has a hammer.

```

#Many to many with rich joins
A rich join is when there are shared values between related objects.
For example a doctor with different procedure costs.

use the $richJoin array ('property' => [...cols...])
```php
class Doctor extends Dbaser\Model {
	static protected $manyToMany = ['procedures' => 'Procedure'];
	static protected $richJoin = ['procedures' => ['price']];
}

$doc = Doctor::find(1); // find doctor pk 1
$procedures = $doc->procedures; // get all related procedures in collection

$price_1 = $procedures[0]->price; // get the price as it relates to the first procedure of doctor 1

```


#Has one/has many relations:
works the same way, except the column in the db should reflect the property name.
in the case of a has many relation, there is no column in the table.
```php
class Doctors extends Dbaser\Model {
	static protected $hasOne     = ['nurse' => 'Nurse']; // prop => class
	static protected $hasMany    = ['bosses' => 'Boss']; // prop => class
}
```