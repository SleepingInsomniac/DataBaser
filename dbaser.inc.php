<?php

require "Object.class.php";          // for simulating computed properties (like we can in real OOP languages)
require "Base.class.php";            // for talking with the database
require "Model.class.php";           // for handling model functions
require "Query.class.php";           // for creating an SQL query (dynamically)
require "ModelCollection.class.php"; // for handling model relations
require "Inflector.class.php";       // for handling pluralization rules etc.