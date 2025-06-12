// Import CSS
import '../styles/app.scss';

// Import jQuery
const $ = require('jquery');

// Import Popper.js
const Popper = require('@popperjs/core');

// Import Bootstrap
require('bootstrap');

// Import DataTables and its extensions
require('datatables.net-bs5');
require('datatables.net-responsive-bs5');

// Import your custom script

// Initialiser DataTables
$(document).ready(function() {
    $('#example').DataTable();
});
