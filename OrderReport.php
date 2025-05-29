// app/reports/OrderReport.php
class OrderReport extends FPDF {
    function generate() {
        $this->AddPage();
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,'Relatorio de Ordens de Servico',0,1);
        // ... conteúdo dinâmico ...
        $this->Output('D','relatorio.pdf');
    }
}
