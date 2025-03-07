<?php

namespace MscConverter\Writers;

use MscConverter\Observers\Events\NoticeMessageEvent;
use MscConverter\Observers\ObserverInterface;
use Override;
use PDO;
use RuntimeException;

class SqliteWriter implements WriterInterface {
    
    public readonly string $dbFilePath;
    
    private readonly PDO $dbh;
    
    private int $remessa;
    
    private ObserverInterface $events;

    public function __construct(string $dbFilePath) {
        $this->dbFilePath = $dbFilePath;
    }
    
    private function createMscTable(): void {
        $this->events->notify(new NoticeMessageEvent('Criando tabela da MSC...'));
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
        if($this->dbh->exec($sqlCreateTable) === false){
            throw new RuntimeException('Falha ao criar a tabela "msc".');
        }
    }
    
    protected function deleteOldData(): void {
        $this->events->notify(new NoticeMessageEvent('Apagando dados antigos...'));
        $sqlDeleteOldData = "DELETE FROM msc WHERE remessa = {$this->remessa};";
        if($this->dbh->exec($sqlDeleteOldData) === false){
            throw new RuntimeException('Falha ao limpar a tabela "msc".');
        }
    }
    
    #[Override]
    public function prepare(int $remessa): void {
        $this->events->notify(new NoticeMessageEvent('Preparando banco de dados de destino...'));
        $this->remessa = $remessa;
        $this->dbh = new PDO("sqlite:{$this->dbFilePath}");
        $this->createMscTable();
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
        $stmt = $this->dbh->prepare($query);
        $this->dbh->beginTransaction();
        $this->deleteOldData();
    }

    #[Override]
    public function save(): void {
        $this->dbh->commit();
        $this->events->notify(new NoticeMessageEvent("Dados salvos em {$this->dbFilePath}"));
    }

    #[Override]
    public function storeRow(array $data): void {
        $row = [];
        foreach ($data as $key => $value){
            $row[':'.$key] = $value;
        }
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
        $stmt = $this->dbh->prepare($query);
        if($stmt->execute($row) === false){
            $this->dbh->rollBack();
            throw new RuntimeException("Falha ao inserir a linha tabela msc.");
        }
    }
    
    public function setEventManager(ObserverInterface $events): SqliteWriter {
        $this->events = $events;
        return $this;
    }
}
