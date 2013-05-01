/*****************************************************************
 * file: reporting.js
 *
 *****************************************************************/

jQuery(document).ready(
    function($) {
        // Make tables sortable
         var tstts = $("#topsearches_table").tablesorter( {sortList: [[1,1]]} );
         var trtts = $("#topresults_table").tablesorter( {sortList: [[5,1]]} );

         // Export Results Click
         //
         jQuery('#export_results').click(
            function(e) {
               var data = {
                  action: 'slp_download_report_csv',
                  filename: 'topresults',
                  query: jQuery("[name=topresults]").val(),
                  sort: trtts[0].config.sortList.toString(),
                  all: jQuery("[name=export_all]").is(':checked')
               };
               jQuery('#secretIFrame').attr('src',
                    ajaxurl + '?' + jQuery.param(data)
                );
            }
         );

        // Export Searches Button Click
        //
         jQuery('#export_searches').click(
            function(e) {
               var data = {
                  action: 'slp_download_report_csv',
                  filename: 'topsearches',
                  query: jQuery("[name=topsearches]").val(),
                  sort: tstts[0].config.sortList.toString(),
                  all: jQuery("[name=export_all]").is(':checked')
               };
               jQuery('#secretIFrame').attr('src',
                    ajaxurl + '?' + jQuery.param(data)
                );
            }
         );

    }
);
