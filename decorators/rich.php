<?php
class Kint_Decorators_Rich extends Kint
{
	public static function decorate( kintVariableData $kintVar, $callerArr=null )
	{
		$output = '<dl>';

		$extendedPresent = $kintVar->extendedValue !== null || $kintVar->alternatives !== null;

		if ( $extendedPresent ) {
			$class = 'kint-parent';
			if ( Kint::$expandedByDefault ) {
				$class .= ' kint-show';
			}
			$output .= '<dt class="' . $class . '"><nav></nav>';
		} else {
			$output .= '<dt>';
		}

		$output .= self::_drawHeader( $kintVar ) . $kintVar->value . '</dt>';

		if ( $extendedPresent ) {
			$output .= '<dd>';
		}

		if ( isset( $kintVar->extendedValue ) ) {

			if ( is_array( $kintVar->extendedValue ) ) {
				foreach ( $kintVar->extendedValue as $v ) {
					$output .= self::decorate( $v );
				}
			} elseif ( is_string( $kintVar->extendedValue ) ) {
				$output .= '<pre>' . $kintVar->extendedValue . '</pre>';
			} else {
				$output .= self::decorate( $kintVar->extendedValue ); //it's kint's container
			}

		} elseif ( isset( $kintVar->alternatives ) ) {
			$output .= "<ul class=\"kint-tabs\">";

			foreach ( $kintVar->alternatives as $k => $var ) {
				$active = $k === 0 ? ' class="kint-active-tab"' : '';
				$output .= "<li{$active}>" . self::_drawHeader( $var, false ) . '</li>';
			}

			$output .= "</ul><ul>";

			foreach ( $kintVar->alternatives as $var ) {
				$output .= "<li>";

				$var = $var->value;

				if ( is_array( $var ) ) {
					foreach ( $var as $v ) {
						$output .= self::decorate( $v );
					}
				} elseif ( is_string( $var ) ) {
					$output .= '<pre>' . $var . '</pre>';
				} elseif ( isset( $var ) ) {
					throw new Exception(
						'Kint has encountered an error, '
							. 'please paste this report to https://github.com/raveren/kint/issues<br>'
							. 'Error encountered at ' . basename( __FILE__ ) . ':' . __LINE__ . '<br>'
							. ' variables: '
							. htmlspecialchars( var_export( $kintVar->alternatives, true ), ENT_QUOTES )
					);
				}

				$output .= "</li>";
			}

			$output .= "</ul>";
		}
		if ( $extendedPresent ) {
			$output .= '</dd>';
		}

		$output .= '</dl>';
        if($callerArr !== null){
            $output .= self::_showCaller( $callerArr[0], $callerArr[1] );
        }
		return $output;
	}

	private static function _drawHeader( kintVariableData $kintVar, $verbose = true )
	{
		$output = '';
		if ( $verbose ) {
			if ( $kintVar->access !== null ) {
				$output .= "<var>" . $kintVar->access . "</var> ";
			}

			if ( $kintVar->name !== null && $kintVar->name !== '' ) {
				$output .= "<dfn>" . $kintVar->name . "</dfn> ";
			}

			if ( $kintVar->operator !== null ) {
				$output .= $kintVar->operator . " ";
			}
		}

		if ( $kintVar->type !== null ) {
			$output .= "<var>" . $kintVar->type;
			if ( $kintVar->subtype !== null ) {
				$output .= " " . $kintVar->subtype;
			}
			$output .= "</var> ";
		}


		if ( $kintVar->size !== null ) {
			$output .= "(" . $kintVar->size . ") ";
		}

		return $output;
	}


	/**
	 * produces css and js required for display. May be called multiple times, will only produce output once per
	 * pageload or until `-` or `@` modifier is used
	 *
	 * @return string
	 */
	protected static function _css()
	{
		if ( !self::$_firstRun ) return '';
		self::$_firstRun = false;

		$baseDir = KINT_DIR . 'view/inc/';

		if ( !is_readable( $cssFile = $baseDir . self::$theme . '.css' ) ) {
			$cssFile = $baseDir . 'original.css';
		}

		return '<script>' . file_get_contents( $baseDir . 'kint.js' ) . '</script>'
			. '<style>' . file_get_contents( $cssFile ) . "</style>\n";
	}


	/**
	 * called for each dump, opens the html tag
	 *
	 * @return string
	 */
	protected static function _wrapStart()
	{
		return "<div class=\"kint\">";
	}


	/**
	 * closes Kint::_wrapStart() started html tags and displays callee information
	 *
	 * @return string
	 */
	protected static function _wrapEnd()
	{
        return '</div>';
	}

    private static function _showCaller( $callee, $prevCaller ){
        if ( !Kint::$displayCalledFrom ) {
            return '';
        }

        $callingFunction = '';
        if ( isset( $prevCaller['class'] ) ) {
            $callingFunction = $prevCaller['class'];
        }
        if ( isset( $prevCaller['type'] ) ) {
            $callingFunction .= $prevCaller['type'];
        }
        if ( isset( $prevCaller['function'] ) && !in_array( $prevCaller['function'], Kint::$_statements ) ) {
            $callingFunction .= $prevCaller['function'] . '()';
        }
        $callingFunction and $callingFunction = " in ({$callingFunction})";


        $calleeInfo = isset( $callee['file'] )
            ? 'Called from ' . self::shortenPath( $callee['file'], $callee['line'] )
            : '';
        return $calleeInfo || $callingFunction
            ? "<pre>{$calleeInfo}{$callingFunction}</pre>"
            : "";
    }

}