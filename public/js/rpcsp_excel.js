(function () {
  function xml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  }

  function cell(value, style, mergeAcross, type) {
    var merge = mergeAcross ? ' ss:MergeAcross="' + mergeAcross + '"' : '';
    return '<Cell ss:StyleID="' + (style || 'Body') + '"' + merge + '><Data ss:Type="' + (type || 'String') + '">' + xml(value) + '</Data></Cell>';
  }

  window.exportRpcspExcel = function (report) {
    var rows = report.rows || [];
    var tableRows = rows.map(function (row) {
      return '<Row ss:AutoFitHeight="1">' +
        cell(row.article, 'BodyLeft') +
        cell(row.description, 'BodyLeft') +
        cell(row.propertyNumber, 'Body') +
        cell(row.unit, 'Body') +
        cell(row.unitValue, 'Money', 0, 'Number') +
        cell(row.balance, 'Body', 0, 'Number') +
        cell(row.onHand, 'Body', 0, 'Number') +
        cell(row.shortageQuantity || '', 'Body') +
        cell(row.shortageValue || '', row.shortageValue ? 'Money' : 'Body', 0, row.shortageValue ? 'Number' : 'String') +
        cell(row.remarks, 'BodyLeft') +
        cell(row.date, 'Body') +
      '</Row>';
    }).join('');

    var workbook = '<?xml version="1.0"?>' +
      '<?mso-application progid="Excel.Sheet"?>' +
      '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel">' +
      '<Styles>' +
        '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="9"/></Style>' +
        '<Style ss:ID="Title"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>' +
        '<Style ss:ID="Subtitle"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>' +
        '<Style ss:ID="Info"><Font ss:FontName="Arial" ss:Size="10"/></Style>' +
        '<Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Font ss:FontName="Arial" ss:Size="9" ss:Bold="1"/></Style>' +
        '<Style ss:ID="Body"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>' +
        '<Style ss:ID="BodyLeft" ss:Parent="Body"><Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/></Style>' +
        '<Style ss:ID="Money" ss:Parent="Body"><NumberFormat ss:Format="₱#,##0.00"/></Style>' +
        '<Style ss:ID="Summary"><Alignment ss:Horizontal="Right"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/><NumberFormat ss:Format="₱#,##0.00"/></Style>' +
        '<Style ss:ID="SignTitle"><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/></Style>' +
        '<Style ss:ID="SignName"><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/></Style>' +
        '<Style ss:ID="Sign"><Font ss:FontName="Arial" ss:Size="10"/></Style>' +
      '</Styles>' +
      '<Worksheet ss:Name="RPCSP"><Table ss:ExpandedColumnCount="11" ss:ExpandedRowCount="' + (rows.length + 19) + '" x:FullColumns="1" x:FullRows="1">' +
        '<Column ss:Width="58"/><Column ss:Width="110"/><Column ss:Width="130"/><Column ss:Width="66"/><Column ss:Width="78"/><Column ss:Width="72"/><Column ss:Width="66"/><Column ss:Width="60"/><Column ss:Width="70"/><Column ss:Width="90"/><Column ss:Width="78"/>' +
        '<Row ss:Height="22">' + cell('REPORT ON THE PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY', 'Title', 10) + '</Row>' +
        '<Row>' + cell('MILITARY, POLICE & SECURITY EQUIPMENT (ACCOUNT CODE: 1-04-05-090-00)', 'Subtitle', 10) + '</Row>' +
        '<Row>' + cell('(Type of Property, Plant and Equipment)', 'Subtitle', 10) + '</Row>' +
        '<Row>' + cell('As of ' + report.asOf, 'Subtitle', 10) + '</Row>' +
        '<Row ss:Height="10">' + cell('', 'Info', 10) + '</Row>' +
        '<Row>' + cell('Fund Cluster: ' + report.fund, 'Info', 10) + '</Row>' +
        '<Row>' + cell('For which: ' + report.officer + ', ' + report.designation + ' is accountable, having assumed such accountability on ' + report.assumption + '.', 'Info', 10) + '</Row>' +
        '<Row ss:Height="34">' +
          cell('ARTICLE', 'Header') + cell('DESCRIPTION', 'Header') + cell('SEMI-EXPENDABLE PROPERTY NUMBER', 'Header') + cell('UNIT OF MEASURE', 'Header') + cell('UNIT VALUE', 'Header') + cell('BALANCE PER PROPERTY CARD', 'Header') + cell('ON HAND PER COUNT', 'Header') + cell('SHORTAGE / OVERAGE QUANTITY', 'Header') + cell('SHORTAGE / OVERAGE VALUE', 'Header') + cell('REMARKS', 'Header') + cell('DATE', 'Header') +
        '</Row>' + tableRows +
        '<Row><Cell ss:Index="7" ss:StyleID="Summary" ss:MergeAcross="1"><Data ss:Type="String">Total Property Count: ' + xml(rows.length) + '</Data></Cell>' + cell(report.totalValue, 'Summary', 2, 'Number') + '</Row>' +
        '<Row ss:Height="12">' + cell('', 'Info', 10) + '</Row>' +
        '<Row>' + cell('Certified Correct by:', 'SignTitle', 2) + cell('Approved by:', 'SignTitle', 2) + cell('Witnessed by:', 'SignTitle', 4) + '</Row>' +
        '<Row ss:Height="34">' + cell('', 'Sign', 10) + '</Row>' +
        '<Row>' + cell('MS ROSEMARIE O VILBAR, MPA', 'SignName', 2) + cell('ANTHONY A BACUS', 'SignName', 2) + cell('Mr Darrell Antoni J Wong', 'SignName', 4) + '</Row>' +
        '<Row>' + cell('CHIEF, PAOGS, APAO, PA', 'Sign', 2) + cell('LTC INF (GSC) PA', 'Sign', 2) + cell('Rep, COA, HPA', 'Sign', 4) + '</Row>' +
        '<Row>' + cell('TEAM LEADER', 'Sign', 2) + cell('Commanding Officer', 'Sign', 2) + cell('Member', 'Sign', 4) + '</Row>' +
        '<Row ss:Height="24">' + cell('', 'Sign', 10) + '</Row>' +
        '<Row>' + cell('Mr Jan Harold C Novo CE', 'SignName', 2) + cell('', 'Sign', 7) + '</Row>' +
        '<Row>' + cell('UPO, 4ID, PA', 'Sign', 2) + cell('', 'Sign', 7) + '</Row>' +
        '<Row>' + cell('Member', 'Sign', 2) + cell('', 'Sign', 7) + '</Row>' +
      '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><PageSetup><Layout x:Orientation="Landscape"/><PageMargins x:Bottom="0.3" x:Left="0.3" x:Right="0.3" x:Top="0.3"/></PageSetup><FitToPage/><Print><FitWidth>1</FitWidth><FitHeight>0</FitHeight><ValidPrinterInfo/></Print><Selected/></WorksheetOptions></Worksheet></Workbook>';

    var blob = new Blob(['\uFEFF' + workbook], { type: 'application/vnd.ms-excel;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = report.filename || 'RPCSP.xls';
    link.click();
    URL.revokeObjectURL(url);
  };
})();
