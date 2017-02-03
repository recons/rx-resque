<?php

namespace RxResque;

use RxResque\Channel\ChannelInterface;

interface StrandInterface extends ChannelInterface, ContextInterface {}