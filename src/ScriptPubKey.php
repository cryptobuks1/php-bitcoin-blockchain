<?php

declare(strict_types=1);

namespace AndKom\PhpBitcoinBlockchain;

use AndKom\PhpBitcoinBlockchain\Network\Bitcoin;
use function BitWasp\Bech32\encodeSegwit;

/**
 * Class ScriptPubKey
 * @package AndKom\PhpBitcoinBlockchain
 */
class ScriptPubKey extends Script
{
    /**
     * @return bool
     */
    public function isReturn(): bool
    {
        $operations = $this->parse();

        return count($operations) >= 1 &&
            $operations[0]->code == Opcodes::OP_RETURN;
    }

    /**
     * @return bool
     */
    public function isPayToPubKey(): bool
    {
        $operations = $this->parse();

        return count($operations) == 2 &&
            ($operations[0]->size == 33 || $operations[0]->size == 65) &&
            $operations[1]->code == Opcodes::OP_CHECKSIG;
    }

    /**
     * @return bool
     */
    public function isPayToPubKeyHash(): bool
    {
        $operations = $this->parse();

        return count($operations) == 5 &&
            $operations[0]->code == Opcodes::OP_DUP &&
            $operations[1]->code == Opcodes::OP_HASH160 &&
            $operations[2]->size == 20 &&
            $operations[3]->code == Opcodes::OP_EQUALVERIFY &&
            $operations[4]->code == Opcodes::OP_CHECKSIG;
    }

    /**
     * @return bool
     */
    public function isPayToScriptHash(): bool
    {
        $operations = $this->parse();

        return count($operations) == 3 &&
            $operations[0]->code == Opcodes::OP_HASH160 &&
            $operations[1]->size == 20 &&
            $operations[2]->code == Opcodes::OP_EQUAL;
    }

    /**
     * @return bool
     */
    public function isMultisig(): bool
    {
        $operations = $this->parse();

        return ($count = count($operations)) >= 4 &&
            ord($operations[0]->data) >= 1 &&
            ord($operations[$count - 2]->data) >= 1 &&
            $operations[$count - 1]->code == Opcodes::OP_CHECKMULTISIG;
    }

    /**
     * @return bool
     */
    public function isPayToWitnessPubKeyHash(): bool
    {
        $operations = $this->parse();

        return (count($operations) == 2) &&
            ord($operations[0]->data) == 0 &&
            $operations[1]->size == 20;
    }

    /**
     * @return bool
     */
    public function isPayToWitnessScriptHash(): bool
    {
        $operations = $this->parse();

        return (count($operations) == 2) &&
            ord($operations[0]->data) == 0 &&
            $operations[1]->size == 32;
    }

    /**
     * @param Bitcoin|null $network
     * @return string
     * @throws Exception
     * @throws \BitWasp\Bech32\Exception\Bech32Exception
     */
    public function getOutputAddress(Bitcoin $network = null): string
    {
        if (!$network) {
            $network = new Bitcoin();
        }

        $operations = $this->parse();

        if ($this->isPayToPubKey()) {
            return Utils::pubKeyToAddress($operations[0]->data, $network::P2PKH_PREFIX);
        }

        if ($this->isPayToPubKeyHash()) {
            return Utils::hash160ToAddress($operations[2]->data, $network::P2PKH_PREFIX);
        }

        if ($this->isPayToScriptHash()) {
            return Utils::hash160ToAddress($operations[1]->data, $network::P2SH_PREFIX);
        }

        if ($this->isPayToWitnessPubKeyHash()) {
            return encodeSegwit($network::BECH32_HRP, $operations[0]->data, $operations[1]->data);
        }

        if ($this->isPayToWitnessScriptHash()) {
            return encodeSegwit($network::BECH32_HRP, $operations[0]->data, $operations[1]->data);
        }

        throw new Exception('Unable to decode output address.');
    }
}