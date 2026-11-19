ALTER TABLE midias
    ADD COLUMN IF NOT EXISTS conteudo_id INT REFERENCES conteudos(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS idx_midias_conteudo ON midias(conteudo_id);
