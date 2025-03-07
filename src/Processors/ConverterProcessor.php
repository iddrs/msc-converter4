<?php

namespace MscConverter\Processors;

use MscConverter\Observers\Events\InfoMessageEvent;
use MscConverter\Observers\Events\NoticeMessageEvent;
use MscConverter\Observers\Events\ProgressEvent;
use MscConverter\Observers\ObserverInterface;
use MscConverter\Readers\ReaderInterface;
use MscConverter\Writers\WriterInterface;
use PDO;
use RuntimeException;

class ConverterProcessor
{

    private ObserverInterface $events;
    private readonly ReaderInterface $reader;
    private readonly WriterInterface $writer;


    private readonly PDO $dbh;

    public function __construct(ReaderInterface $reader, WriterInterface $writer)
    {
        $this->reader = $reader;
        $this->writer = $writer;
    }

    public function convert(): void
    {
        $this->events->notify(new InfoMessageEvent(sprintf('Iniciando a conversão da MSC em %s', date('d/m/Y, H:i:s'))));
        $query = '
            INSERT INTO "msc" (
                "remessa",
                "cod_instituicao_siconfi",
                "conta_contabil",
                "poder",
                "orgao",
                "financeiro_permanente",
                "divida_consolidada",
                "exercicio_origem_recurso",
                "fonte_recurso",
                "codigo_acompanhamento_orcamentario",
                "natureza_receita",
                "natureza_despesa",
                "funcao",
                "subfuncao",
                "ano_inscricao_restos_a_pagar",
                "saldo_inicial",
                "movimento_devedor",
                "movimento_credor",
                "saldo_final"
            ) VALUES (
                :remessa,
                :cod_instituicao_siconfi,
                :conta_contabil,
                :poder,
                :orgao,
                :financeiro_permanente,
                :divida_consolidada,
                :exercicio_origem_recurso,
                :fonte_recurso,
                :codigo_acompanhamento_orcamentario,
                :natureza_receita,
                :natureza_despesa,
                :funcao,
                :subfuncao,
                :ano_inscricao_restos_a_pagar,
                :saldo_inicial,
                :movimento_devedor,
                :movimento_credor,
                :saldo_final
            );
        ';
        $this->prepareTempDb();
        $stmt = $this->dbh->prepare($query);
        $this->dbh->beginTransaction();
        $this->events->notify(new InfoMessageEvent("Carregando dados da MSC..."));
        $this->reader->load();
        $this->events->notify(new InfoMessageEvent('Processando as linhas da MSC...'));
        $lineno = 1;
        $progress = new ProgressEvent($lineno, $this->reader->getTotalRows());
        while ($line = $this->reader->readRow()) {
            $progress->current = $lineno;
            $this->events->notify($progress);
            $row = $this->parseRow($line);
            $data = [];
            foreach ($row as $key => $value) {
                $data[':' . $key] = $value;
            }
            if ($stmt->execute($data) === false) {
                $this->dbh->rollBack();
                throw new RuntimeException("Falha ao inserir a linha $lineno na tabela temporária msc.");
            }
            $lineno++;
        }
        $this->dbh->commit();
        $this->writeData();

        $this->events->notify(new InfoMessageEvent(sprintf('Conversão da MSC terminada em %s', date('d/m/Y, H:i:s'))));
    }

    protected function writeData(): void
    {
        $this->events->notify(new InfoMessageEvent('Salvando os dados...'));
        $this->writer->prepare($this->reader->remessa);

        $query = '
            SELECT
                "remessa",
                "cod_instituicao_siconfi",
                "conta_contabil",
                "poder",
                "orgao",
                "financeiro_permanente",
                "divida_consolidada",
                "exercicio_origem_recurso",
                "fonte_recurso",
                "codigo_acompanhamento_orcamentario",
                "natureza_receita",
                "natureza_despesa",
                "funcao",
                "subfuncao",
                "ano_inscricao_restos_a_pagar",
                sum("saldo_inicial") AS "saldo_inicial",
                SUM("movimento_devedor") AS "movimento_devedor",
                SUM("movimento_credor") AS "movimento_credor",
                SUM("saldo_final") AS "saldo_final"
            FROM "msc"
            GROUP BY
                "remessa",
                "cod_instituicao_siconfi",
                "conta_contabil",
                "poder",
                "orgao",
                "financeiro_permanente",
                "divida_consolidada",
                "exercicio_origem_recurso",
                "fonte_recurso",
                "codigo_acompanhamento_orcamentario",
                "natureza_receita",
                "natureza_despesa",
                "funcao",
                "subfuncao",
                "ano_inscricao_restos_a_pagar"
            ORDER BY
                "conta_contabil" ASC,
                "poder" ASC,
                "orgao" ASC,
                "financeiro_permanente" ASC,
                "divida_consolidada" ASC,
                "exercicio_origem_recurso" ASC,
                "fonte_recurso" ASC,
                "codigo_acompanhamento_orcamentario" ASC,
                "natureza_receita" ASC,
                "natureza_despesa" ASC,
                "funcao" ASC,
                "subfuncao" ASC,
                "ano_inscricao_restos_a_pagar" ASC;
        ';

        $result = $this->dbh->query($query, PDO::FETCH_ASSOC);
        if ($result === false) {
            throw new RuntimeException('Falha ao consolidar dados da tabela temporária "msc".');
        }
        /*
         * Preciso realizar fetchAll para poder contar as linhas totais.
         * Se eu tentar fazer foreach direto com $result, o cursor estará no fim do iterador e não funciona.
         * Isto provavelmente gasta mais memória (usando fetchAll), mas não parece ser um problema, por enquanto.
         */
        $data = $result->fetchAll();
        $lines = count($data);
        $this->events->notify(new NoticeMessageEvent("Escrevendo $lines registros..."));
        $lineno = 1;
        $progress = new ProgressEvent($lineno, $lines);
        foreach ($data as $row) {
            $progress->current = $lineno;
            $this->events->notify($progress);
            $this->writer->storeRow($row);
            $lineno++;
        }

        $this->writer->save();

    }


    protected function prepareTempDb(): void
    {
        // $tempDSN = "sqlite:" . __DIR__ . "/temp/msc.db";
        $tempDSN = "sqlite:" . sys_get_temp_dir() . "/msc.db";
        $this->events->notify(new NoticeMessageEvent("Preparando banco de dados temporário $tempDSN"));
        $this->dbh = new PDO($tempDSN);
        $this->createTempTable();
        $this->deleteOldTempData();

    }

    protected function deleteOldTempData(): void
    {
        $this->events->notify(new NoticeMessageEvent('Limpando banco de dados temporário...'));
        $sqlDeleteOldTempData = 'DELETE FROM "msc";';
        if ($this->dbh->exec($sqlDeleteOldTempData) === false) {
            throw new RuntimeException('Falha ao limpar a tabela temporária "msc".');
        }
    }

    protected function createTempTable(): void
    {
        $this->events->notify(new NoticeMessageEvent('Criando tabela msc no banco de dados temporário...'));
        $sqlCreateTable = '
            CREATE TABLE IF NOT EXISTS "msc" (
                "remessa"                                   INTEGER,
                "cod_instituicao_siconfi"                   TEXT,
                "conta_contabil"                            TEXT,
                "poder"                                     INTEGER,
                "orgao"                                     INTEGER,
                "financeiro_permanente"                     INTEGER,
                "divida_consolidada"                        INTEGER,
                "exercicio_origem_recurso"          	INTEGER,
                "fonte_recurso"                             INTEGER,
                "codigo_acompanhamento_orcamentario"	INTEGER,
                "natureza_receita"                          TEXT,
                "natureza_despesa"                          TEXT,
                "funcao"                                    INTEGER,
                "subfuncao"                                 INTEGER,
                "ano_inscricao_restos_a_pagar"              INTEGER,
                "saldo_inicial"                             REAL,
                "movimento_devedor"                         REAL,
                "movimento_credor"                          REAL,
                "saldo_final"                               REAL
            );
        ';
        if ($this->dbh->exec($sqlCreateTable) === false) {
            throw new RuntimeException('Falha ao criar a tabela temporária "msc".');
        }
    }

    protected function parseRow(array $data): array
    {
        // Este método faz muita coisa, mas vou deixar assim, por enquanto.
        $remessa = $this->reader->remessa;
        $codInstituicaoSiconfi = $this->reader->codInstituicaoSiconfi;
        $contaContabil = (string) $data[0];
        $valor = $data[13];
        $tipoValor = $data[14];
        $naturezaValor = $data[15];
        $poder = 0;
        $orgao = 0;
        $financeiroPermanente = 0;
        $dividaConsolidada = 0;
        $exercicioOrigemRecurso = 0;
        $fonteRecurso = 0;
        $codigoAcompanhamentoOrcamentario = 0;
        $naturezaReceita = '';
        $naturezaDespesa = '';
        $funcao = 0;
        $subfuncao = 0;
        $anoInscricaoRestosAPagar = 0;
        $saldoInicial = 0.0;
        $movimentoDevedor = 0.0;
        $movimentoCredor = 0.0;
        $saldoFinal = 0.0;

        $ic = [
            [1, 2],
            [3, 4],
            [5, 6],
            [7, 8],
            [9, 10],
            [11, 12],
        ];

        foreach ($ic as $item) {
            $icValor = $item[0];
            $icTipo = $item[1];
            switch ($data[$icTipo]) {
                case 'PO':
                    $poder = (int) substr($data[$icValor], 0, 2);
                    $orgao = (int) substr($data[$icValor], 2, 3);
                    break;
                case 'FP':
                    $financeiroPermanente = (int) $data[$icValor];
                    break;
                case 'DC':
                    $dividaConsolidada = (int) $data[$icValor];
                    break;
                case 'FR':
                    $exercicioOrigemRecurso = (int) $data[$icValor][0];
                    $fonteRecurso = (int) substr($data[$icValor], 1);
                    break;
                case 'CO':
                    $codigoAcompanhamentoOrcamentario = (int) $data[$icValor];
                    break;
                case 'NR':
                    $naturezaReceita = (string) $data[$icValor];
                    break;
                case 'ND':
                    $naturezaDespesa = (string) $data[$icValor];
                    break;
                case 'FS':
                    $funcao = (int) substr($data[$icValor], 0, 2);
                    $subfuncao = (int) substr($data[$icValor], 2);
                    break;
                case 'AI':
                    $anoInscricaoRestosAPagar = (int) $data[$icValor];
                    break;
                default:
                    break;
            }

            if ($tipoValor === 'beginning_balance') {
                switch ($contaContabil[0]) {
                    case '1':
                    case '3':
                    case '5':
                    case '7':
                        if ($naturezaValor === 'D') {
                            $saldoInicial = (float) $valor;
                        } else {
                            $saldoInicial = ((float) $valor) * -1;
                        }
                        break;
                    case '2':
                    case '4':
                    case '6':
                    case '8':
                        if ($naturezaValor === 'C') {
                            $saldoInicial = (float) $valor;
                        } else {
                            $saldoInicial = ((float) $valor) * -1;
                        }
                        break;
                }
            }

            if ($tipoValor === 'ending_balance') {
                switch ($contaContabil[0]) {
                    case '1':
                    case '3':
                    case '5':
                    case '7':
                        if ($naturezaValor === 'D') {
                            $saldoFinal = (float) $valor;
                        } else {
                            $saldoFinal = ((float) $valor) * -1;
                        }
                        break;
                    case '2':
                    case '4':
                    case '6':
                    case '8':
                        if ($naturezaValor === 'C') {
                            $saldoFinal = (float) $valor;
                        } else {
                            $saldoFinal = ((float) $valor) * -1;
                        }
                        break;
                }
            }

            if ($tipoValor === 'period_change') {
                switch ($naturezaValor) {
                    case 'D':
                        $movimentoDevedor = (float) $valor;
                        break;
                    case 'C':
                        $movimentoCredor = (float) $valor;
                        break;
                }
            }
        }

        return [
            'remessa' => $remessa,
            'cod_instituicao_siconfi' => $codInstituicaoSiconfi,
            'conta_contabil' => $contaContabil,
            'poder' => $poder,
            'orgao' => $orgao,
            'financeiro_permanente' => $financeiroPermanente,
            'divida_consolidada' => $dividaConsolidada,
            'exercicio_origem_recurso' => $exercicioOrigemRecurso,
            'fonte_recurso' => $fonteRecurso,
            'codigo_acompanhamento_orcamentario' => $codigoAcompanhamentoOrcamentario,
            'natureza_receita' => $naturezaReceita,
            'natureza_despesa' => $naturezaDespesa,
            'funcao' => $funcao,
            'subfuncao' => $subfuncao,
            'ano_inscricao_restos_a_pagar' => $anoInscricaoRestosAPagar,
            'saldo_inicial' => $saldoInicial,
            'movimento_devedor' => $movimentoDevedor,
            'movimento_credor' => $movimentoCredor,
            'saldo_final' => $saldoFinal,
        ];
    }

    public function setEventManager(ObserverInterface $events): ConverterProcessor
    {
        $this->events = $events;
        return $this;
    }
}
