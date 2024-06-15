<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDGDASH</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.css"
        rel="stylesheet" />

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.js"></script>
</head>
<style>
@media (max-width: 576px) {

    .table thead th,
    .table tbody td {
        font-size: 8px;
        padding: 5px;
    }

    .btn-search {
        font-size: 11px
    }

    #data_display_period {
        font-size: 20px;
        margin-top: 15px !important;
        margin-bottom: 15px !important;
    }
}
</style>
<body>
    <?php
    $months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
    ];
    ?>
    <div class="container mt-5">
        <div class="mb-3">
            <label for="">Select Year : </label>
            <input type="text" id="datepicker" value="<?php echo date('Y'); ?>" />
        </div>
        <div class="btn-toolbar mb-3" role="toolbar" aria-label="Toolbar with button groups">
            <div class="btn-group w-100" role="group" aria-label="First group">
                <?php for ($i = 0; $i < 12; $i++) { ?>
                <button type="button" class="btn btn-outline-dark btn-search"
                    data-month="<?php echo $i + 1; ?>"><?php echo $months[$i]; ?></button>
                <?php } ?>
            </div>
        </div>
        <h2 class="title text-center mt-5 mb-5" id="data_display_period"><?php echo date('Y-m-d'); ?></h2>
        <div class="datatable-wrapper">
            <table id="example" class="table table-striped table-bordered nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center align-middle" style="background-color: #badc94;">Location</th>
                        <th colspan="2" class="text-center align-middle" style="background-color: #badc94;">NEW MONTHLY</th>
                        <th colspan="2" class="text-center align-middle" style="background-color: #badc94;">NEW 12M</th>
                    <th colspan="2" class="text-center align-middle" style="background-color: #badc94;">Renewals</th>
                        <th rowspan="2" class="text-center align-middle" style="background-color: #badc94;">Grand Total</th>
                    </tr>
                    <tr>
                        <th class="text-center align-middle">No.Signed</th>
                        <th class="text-center align-middle">Total</th>
                        <th class="text-center align-middle">No. Signed</th>
                        <th class="text-center align-middle">Total</th>
                        <th class="text-center align-middle">No. Signed</th>
                        <th class="text-center align-middle">Total</th>
                    </tr>
                </thead>
                <tbody id="data_list"></tbody>
            </table>
        </div>
        <div class="btn-toolbar mt-5" role="toolbar" aria-label="Toolbar with button groups">
            <div class="btn-group w-100" role="group" aria-label="Second group">
                <?php for ($i = 0; $i < 12; $i++) { ?>
                <button type="button" class="btn btn-outline-dark btn-search"
                    data-month="<?php echo $i + 1; ?>"><?php echo $months[$i]; ?></button>
                <?php } ?>
            </div>
        </div>
        <div class="btn-toolbar mt-5 toolbar3" role="toolbar" aria-label="Toolbar with button groups">
            <div class="btn-group w-50" role="group" aria-label="Second group" style="margin: 0 auto;">
                <button type="button" class="btn btn-outline-dark btn-search" data-month="before">Yesterday</button>
                <button type="button" class="btn btn-outline-dark btn-search" data-month="today">Today</button>
            </div>
        </div>
    </div>
    <script>
    // Function to fetch data from the API and populate the table
    function fetchData($month, $year) {
        $.ajax({
            url: 'http://bdgdash.com/server.php', // Change the URL to the actual location of your API script
            method: 'POST',
            dataType: 'json',
            data: {
                'month': $month,
                'year': $year
            },
            success: function(resp) {
                $('#data_list').empty();

                if (resp.error) {
                    $('#data_list').append('<tr>' +
                        '<td class="text-center align-middle" colspan="8">No data found.</td>' +
                        '</tr>');
                } else {
                    resp.data.forEach(function(row) {
                        $('#data_list').append('<tr>' +
                            '<td class="text-center align-middle">' + row.location + '</td>' +
                            '<td class="text-center align-middle">' + (row?.type1 ?? "-") +
                            '</td>' +
                            '<td class="text-center align-middle">' + (row?.type1 > 0 ? "€ " + (
                                54.95 * parseInt(row.type1)).toLocaleString('en-US') : '-') +
                            '</td>' +
                            '<td class="text-center align-middle">' + (row.type2 ?? "-") +
                            '</td>' +
                            '<td class="text-center align-middle">' + (row?.type2 > 0 ? '€ ' + (
                                325 * parseInt(row.type2)).toLocaleString('en-US') : '-') +
                            '</td>' +
                            '<td class="text-center align-middle">' + (row?.type3 ?? "-") +
                            '</td>' +
                            '<td class="text-center align-middle">' + (row?.type3 > 0 ? '€ ' + (
                                325 * parseInt(row.type3)).toLocaleString('en-US') : '-') +
                            '</td>' +
                            '<td class="text-center align-middle">€ ' + ((54.95 * parseInt(row
                                    .type1 ?? 0)) + 325 * parseInt(row.type2 ?? 0) + 325 *
                                parseInt(row.type3 ?? 0)).toLocaleString(
                                'en-US') + '</td>' +
                            '</tr>');
                    });
                }

                $('#data_display_period').text(resp.title);
            },
            error: function(error) {
                console.log('Error fetching data: ', error);
            }
        });
    }
    
    function callAPI() {
        $.ajax({
            url: 'http://bdgdash.com/server.php',
            dataType: 'json',
            method: 'GET',
            success: function(response) {
                // Handle the response data here
                console.log('API call successful');
            },
            error: function(xhr, status, error) {
                // Handle errors here
                console.error('Error making API call:', error);
            }
        });
    }

    // Call the fetchData function on page load
    $(document).ready(function() {
        let currentMonth = "today";
        let currentYear = $('#datepicker').val();

        fetchData(currentMonth, currentYear);

        $('body .btn-search').on('click', function() {
            let month = $(this).attr('data-month');
            let year = $('#datepicker').val();
            $('body .btn-search').removeClass('active');

           $('body .btn-search[data-month="' + month + '"]').addClass('active');
            fetchData(month, year);
        });

        // Call the API initially when the page loads
        callAPI();

        // Set interval to call the API every 30 minutes (30 * 60 * 1000 milliseconds)
        setInterval(callAPI, 5 * 60 * 1000);

        $("#datepicker").datepicker({
            format: "yyyy",
            viewMode: "years",
            minViewMode: "years",
            autoclose: true,
        });

        $('#datepicker').on('change', function() {
            let selYear = $(this).val();
            $('body .btn-search').removeClass('active');

            if (currentYear == selYear) {
                $('.toolbar3').show();
                fetchData('today', selYear);
            } else {
                fetchData(1, selYear);
                $('.toolbar3').hide();
                $('body .btn-search[data-month="1"]').addClass('active');
            }
        })
    });
    </script>

</body>

</html>