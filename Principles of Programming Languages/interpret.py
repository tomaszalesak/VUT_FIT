#!/usr/bin/env python3
#############################
# IPP interpret             #
# Tomáš Zálešák, xzales13   #
# 18.3.2019                 #
#############################

import re
import sys
import xml.etree.ElementTree as ET

# return values
SUCCESS: int = 0
ERR_WRONG_ARG: int = 10
ERR_OPENING_INPUT_FILE: int = 11
ERR_OPENING_OUTPUT_FILE: int = 12
ERR_WRONG_XML_FORMAT: int = 31
ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX: int = 32
ERR_INTERNAL: int = 99

# attributes types: label var type int bool string nil
TYPE_VAR: int = 100
TYPE_BOOL: int = 101
TYPE_LABEL: int = 102
TYPE_TYPE: int = 103
TYPE_NIL: int = 104
TYPE_STRING: int = 105
TYPE_INT: int = 106


def print_help():
    print("""usage: interpret2.py [-h] [--source SOURCE] [--input INPUT] [--stats STATS]
                    [--insts INSTS] [--vars VARS]

argumenty:
  --help            zobrazit tuhle nápovědu
  --source=file     vstupní soubor s XML reprezentací zdrojového kódu
  --input=file      soubor se vstupy pro samotnou interpretaci zadaného
                    zdrojového kódu
  --stats=file      sbírání statistik interpretace kódu
  --insts           výpis počtu vykonaných instrukcí během interpretace do
                    statistik
  --vars            výpis maximálního počtu inicializovaných proměnných
                    přítomných ve všech platných rámcích během interpretace
                    zadaného programu do statistik""")


def get_arguments():
    arguments = {
        "help": False,
        "source": False,
        "input": False,
        "stats": False,
        "insts": False,
        "vars": False
    }
    if len(sys.argv) == 1 or len(sys.argv) > 6:
        Error.exit(ERR_WRONG_ARG, "len(sys.argv) == 1 or len(sys.argv) > 6")
    for arg in sys.argv[1:]:
        if arg == "--help":
            if arguments["help"] is not False:
                Error.exit(ERR_WRONG_ARG, "arguments[\"help\"] is not False")
            else:
                arguments["help"] = True

        elif arg[:9] == "--source=":
            if arguments["source"] is not False:
                Error.exit(ERR_WRONG_ARG, "arguments[\"source\"] is not False")
            else:
                arguments["source"] = arg[9:]

        elif arg[:8] == "--input=":
            if arguments["input"] is not False:
                Error.exit(ERR_WRONG_ARG, "arguments[\"input\"] is not False")
            else:
                arguments["input"] = arg[8:]

        elif arg[:8] == "--stats=":
            if arguments["stats"] is not False:
                Error.exit(ERR_WRONG_ARG, "arguments[\"stats\"] is not False")
            else:
                arguments["stats"] = arg[8:]

        elif arg == "--insts":
            if arguments["insts"] is not False:
                Error.exit(ERR_WRONG_ARG, "arguments[\"insts\"] is not False")
            else:
                arguments["insts"] = True

        elif arg == "--vars":
            if arguments["vars"] is not False:
                Error.exit(ERR_WRONG_ARG, "arguments[\"vars\"] is not False")
            else:
                arguments["vars"] = True
        else:
            Error.exit(ERR_WRONG_ARG, "unknown argument")

    if arguments["help"]:
        if arguments["source"] or arguments["input"] or arguments["stats"] or arguments["insts"] or arguments["vars"]:
            Error.exit(ERR_WRONG_ARG, "arg help is not alone")
        print_help()
        exit(SUCCESS)
    if arguments["source"] is False and arguments["input"] is False:
        Error.exit(ERR_WRONG_ARG, "you must include one or both source and input file")
    if (arguments["insts"] or arguments["vars"]) and arguments["stats"] is False:
        Error.exit(ERR_WRONG_ARG, "arg insts and/or vars must be with stats arg")

    return arguments


class Error:

    @staticmethod
    def exit(code, msg):
        print(msg, file=sys.stderr)
        sys.exit(code)


class Frames:
    GF = dict()
    LF = None
    TF = None
    stack = list()

    @classmethod
    def __what_frame(cls, variable):
        frame = None
        if variable[:3] == "GF@":
            frame = cls.GF
        elif variable[:3] == "TF@":
            frame = cls.TF
        elif variable[:3] == "LF@":
            frame = cls.LF
        else:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong frame prefix")
        if frame is None:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "not initialized frame")
        return frame

    @classmethod
    def __remove_frame_prefix(cls, variable):
        return variable[3:]

    @classmethod
    def add_variable(cls, variable):
        frame = cls.__what_frame(variable)
        variable = cls.__remove_frame_prefix(variable)
        if variable in frame:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "var already in frame")
        else:
            frame[variable] = None

    @classmethod
    def set_variable_value(cls, variable, value):
        frame = cls.__what_frame(variable)
        variable = cls.__remove_frame_prefix(variable)
        if variable not in frame:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "var does not exist")
        if isinstance(value, Variable):
            value = value.value()
        frame[variable] = value

    @classmethod
    def get_variable_value(cls, variable):
        frame = cls.__what_frame(variable)
        variable = cls.__remove_frame_prefix(variable)
        if variable not in frame:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "var does not exist")
        value = frame[variable]
        if value is None:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "non-initialized value")
        return value


class Stack:

    def __init__(self):
        self.stack = list()

    def pop(self):
        if len(self.stack) != 0:
            return self.stack.pop()
        else:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "empty stack")

    def push(self, item):
        self.stack.append(item)


class Labels:
    labels = dict()

    @classmethod
    def go_to_label(cls, label):
        label = label.__str__()
        if label not in cls.labels:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "label does not exist")
        Interpret.instrOrder = cls.labels[label]

    @classmethod
    def add_label(cls, label):
        label = label.__str__()
        if label not in cls.labels:
            cls.labels[label] = Interpret.instruction_order
        else:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "label already in labels")


class Variable:

    def __init__(self, name):
        self.name = name

    def value(self):
        return Frames.get_variable_value(self.name)

    def name(self):
        return self.name

    def set(self, value):
        Frames.set_variable_value(self.name, value)

    def __get_value_of_expected_type(self, expected_type):
        value = self.value()
        if not isinstance(value, expected_type):
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong var type stored in")
        else:
            return value

    def __int__(self):
        return self.__get_value_of_expected_type(int)

    def __str__(self):
        return self.__get_value_of_expected_type(str)

    def __bool__(self):
        return self.__get_value_of_expected_type(bool)


class Symbol:
    pass


class Label:

    def __init__(self, name):
        self.name = name

    def __str__(self):
        return self.name


class Interpret:
    instruction_order = 1
    instructions = list()
    values_stack = Stack()
    call_stack = Stack()

    @classmethod
    def run(cls, root):

        number_of_instructions = len(root)

        for instruction in root:
            if instruction.tag == "instruction":
                if int(instruction.get("order")) <= number_of_instructions:
                    cls.instructions.append(instruction)

                else:
                    Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong instruction order")
            else:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "expected instruction tag")

        cls.instructions.sort(key=cls.sort_instructions)

        order = 1
        for instruction in cls.instructions:
            if int(instruction.get("order")) != order:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong order no")
            else:
                order = order + 1

        for x in cls.instructions:
            if x.attrib["opcode"] == "LABEL":
                cls.instruction_order = int(x.attrib["order"])
                instruction_to_exec = Instruction(x)
                instruction_to_exec.execute()

        cls.instruction_order = 1

        while cls.instruction_order <= number_of_instructions:
            current_instruction = cls.instructions[cls.instruction_order - 1]
            if current_instruction.attrib["opcode"] == "LABEL":
                pass
            else:
                instruction_to_exec = Instruction(current_instruction)
                instruction_to_exec.execute()
            cls.instruction_order += 1

    @staticmethod
    def sort_instructions(instruction):
        return int(instruction.get("order"))

    @staticmethod
    def convert_value(xml_type, xml_value):
        if xml_type == "var":
            if re.match(r"^((LF)|(TF)|(GF))@([a-zA-Z]|-|[_$&%*!?])([a-zA-Z]|-|[_$&%*!?]|[0-9]+)*$", xml_value) is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "var match err")
            return Variable(xml_value)
        elif xml_type == "string":
            if xml_value is None:
                xml_value = ""
            if re.match(r"^([a-zA-Z\u0021\u0022\u0024-\u005B\u005D-\uFFFF]|(\\\\[0-90-90-9]))*$", xml_value) is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "string match err")
            g = re.findall(r"\\([0-9][0-9][0-9])", xml_value)
            g = list(set(g))
            for x in g:
                xml_value = re.sub("\\\\{0}".format(x), chr(int(x)), xml_value)
            return xml_value
        elif xml_type == "int":
            if re.match(r"^[-+]?[0-9]+$", xml_value) is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "int match err")
            return int(xml_value)
        elif xml_type == "nil":
            if xml_value != "nil":
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "nil match err")
            return None
        elif xml_type == "bool":
            boolean = False
            if xml_value == "true":
                boolean = True
            elif xml_value == "false":
                boolean = False
            else:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "bool match err")
            return boolean
        elif xml_type == "type":
            if xml_value == "int" or xml_value == "bool" or xml_value == "string" or xml_value == "nil":
                pass
            else:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "type match err")
            return xml_value
        elif xml_type == "label":
            if re.match(r"^([a-zA-Z]|-|[_$&%*!?])([a-zA-Z]|-|[_$&%*!?]|[0-9]+)*$", xml_value) is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "label match err")
            return Label(xml_value)
        else:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "converting value")


class Instruction:

    def __init__(self, xml_instruction):
        self.op_code = xml_instruction.attrib["opcode"]
        self.arguments = self.__get_arguments(xml_instruction)
        self.no_of_arguments = len(xml_instruction)

    @staticmethod
    def __get_arguments(xml_instruction):
        arguments = [None for a in xml_instruction]
        for arg in xml_instruction:
            if arg.tag == "arg1" or arg.tag == "arg2" or arg.tag == "arg3":
                pass
            else:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong arg tag")
            index = int(arg.tag[3]) - 1
            if index > len(arguments):
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "too big arg no")
            if arguments[index] is None:
                pass
            else:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "None")
            arguments[index] = Interpret.convert_value(arg.attrib["type"], arg.text)
        for argument in arguments:
            if argument is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "None2")
        return arguments

    def check_arguments(self, *expected_arguments):
        if self.no_of_arguments != len(expected_arguments):
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong len of arg")
        expected_arguments = list(expected_arguments)
        i = 0
        for a in self.arguments:
            if expected_arguments[i] == Symbol:
                expected_arguments[i] = [int, bool, str, Variable]
            argument_type = type(a)
            if type(expected_arguments[i]) == type:
                if argument_type != expected_arguments[i]:
                    Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "x")
            elif type(expected_arguments[i]) == list:
                if argument_type not in expected_arguments[i]:
                    Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "xx")
            else:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "xxx")
            i += 1

    def execute(self):
        if self.op_code == "MOVE":
            self.check_arguments(Variable, Symbol)
            self.arguments[0].set(self.arguments[1])
        elif self.op_code == "CREATEFRAME":
            self.check_arguments()
            Frames.TF = {}
        elif self.op_code == "PUSHFRAME":
            self.check_arguments()
            if Frames.TF is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "T frame is not defined")
            Frames.stack.append(Frames.TF)
            Frames.LF = Frames.stack[-1]
            Frames.TF = None
        elif self.op_code == "POPFRAME":
            self.check_arguments()
            if Frames.LF is None:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "L frame is not defined")
            Frames.TF = Frames.stack.pop()
            Frames.LF = None
        elif self.op_code == "DEFVAR":
            self.check_arguments(Variable)
            Frames.add_variable(self.arguments[0].name)
        elif self.op_code == "CALL":
            self.check_arguments(Label)
            Interpret.call_stack.push(Interpret.instruction_order)
            Labels.go_to_label(self.arguments[0])
        elif self.op_code == "RETURN":
            self.check_arguments()
            Interpret.instruction_order = Interpret.call_stack.pop()
        elif self.op_code == "PUSHS":
            self.check_arguments(Symbol)
            Interpret.values_stack.push(self.arguments[0])
        elif self.op_code == "POPS":
            self.check_arguments(Variable)
            self.arguments[0].set(Interpret.values_stack.pop())
        elif self.op_code == "ADD":
            self.check_arguments(Variable, [Variable, int], [Variable, int])
            self.arguments[0].set(int(self.arguments[1]) + int(self.arguments[2]))
        elif self.op_code == "SUB":
            self.check_arguments(Variable, [Variable, int], [Variable, int])
            self.arguments[0].set(int(self.arguments[1]) - int(self.arguments[2]))
        elif self.op_code == "MUL":
            self.check_arguments(Variable, [Variable, int], [Variable, int])
            self.arguments[0].set(int(self.arguments[1]) * int(self.arguments[2]))
        elif self.op_code == "IDIV":
            self.check_arguments(Variable, [Variable, int], [Variable, int])
            a = int(self.arguments[2])
            if a == 0:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "// 0")
            self.arguments[0].set(int(self.arguments[1]) // a)
        elif self.op_code == "LT":
            self.check_arguments(Variable, Symbol, Symbol)
            if isinstance(self.arguments[1], Variable):
                a = self.arguments[1].value()
            else:
                a = self.arguments[1]
            if isinstance(self.arguments[2], Variable):
                b = self.arguments[2].value()
            else:
                b = self.arguments[2]
            self.arguments[0].set(a < b)
        elif self.op_code == "GT":
            self.check_arguments(Variable, Symbol, Symbol)
            if isinstance(self.arguments[1], Variable):
                a = self.arguments[1].value()
            else:
                a = self.arguments[1]
            if isinstance(self.arguments[2], Variable):
                b = self.arguments[2].value()
            else:
                b = self.arguments[2]
            self.arguments[0].set(a > b)
        elif self.op_code == "EQ":
            self.check_arguments(Variable, Symbol, Symbol)
            if isinstance(self.arguments[1], Variable):
                a = self.arguments[1].value()
            else:
                a = self.arguments[1]
            if isinstance(self.arguments[2], Variable):
                b = self.arguments[2].value()
            else:
                b = self.arguments[2]
            self.arguments[0].set(a == b)
        elif self.op_code == "AND":
            self.check_arguments(Variable, [bool, Variable], [bool, Variable])
            self.arguments[0].set(bool(self.arguments[1]) and bool(self.arguments[2]))
        elif self.op_code == "OR":
            self.check_arguments(Variable, [bool, Variable], [bool, Variable])
            self.arguments[0].set(bool(self.arguments[1]) or bool(self.arguments[2]))
        elif self.op_code == "NOT":
            self.check_arguments(Variable, [bool, Variable])
            self.arguments[0].set(not bool(self.arguments[1]))
        elif self.op_code == "INT2CHAR":
            self.check_arguments(Variable, [Variable, int])
            try:
                self.arguments[0].set(chr(self.arguments[1]))
            except:
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "INT2CHAR value err")
        elif self.op_code == "STRI2INT":
            self.check_arguments(Variable, [Variable, str], [Variable, int])
            string = str(self.arguments[1])
            position = int(self.arguments[2])
            if position < len(string):
                self.arguments[0].set(ord(string[position]))
            else:
                Error.exit(58, "INT2CHAR value err")
        elif self.op_code == "READ":
            # self.check_arguments(Variable, str)
            self.arguments[0].set(Interpret.convert_value(self.arguments[1], input_file))
        elif self.op_code == "WRITE":
            self.check_arguments(Symbol)
            if isinstance(self.arguments[0], Variable):
                a = self.arguments[0].value()
            else:
                a = self.arguments[0]
            if isinstance(a, bool):
                if a is True:
                    a = "true"
                else:
                    a = "false"
            print(str(a), end="")
        elif self.op_code == "CONCAT":
            self.check_arguments(Variable, [str, Variable], [str, Variable])
            self.arguments[0].set(str(self.arguments[1]) + str(self.arguments[2]))
        elif self.op_code == "STRLEN":
            self.check_arguments(Variable, [str, Variable])
            self.arguments[0].set(len(str(self.arguments[1])))
        elif self.op_code == "GETCHAR":
            self.check_arguments(Variable, [Variable, str], [Variable, int])
            string = str(self.arguments[1])
            position = int(self.arguments[2])
            if position < len(string):
                self.arguments[0].set(string[position])
            else:
                Error.exit(58, "INT2CHAR value err")
        elif self.op_code == "SETCHAR":
            self.check_arguments(Variable, [int, Variable], [str, Variable])
            string = str(self.arguments[0])
            position = int(self.arguments[1])
            char = str(self.arguments[2])
            if position >= len(string):
                Error.exit(58, "SETCHAR wrong position")
            if len(char) == 0:
                Error.exit(58, "SETCHAR len is 0")
            string[position] = char[0]
            self.arguments[0].set(string)
        elif self.op_code == "TYPE":
            self.check_arguments(Variable, Symbol)
            if isinstance(self.arguments[1], Variable):
                value = self.arguments[1].value()
            else:
                value = self.arguments[1]
            value_type = type(value).__name__
            if value_type == "None":
                self.arguments[0].set("nil")
            else:
                self.arguments[0].set(value_type)
        elif self.op_code == "LABEL":
            self.check_arguments(Label)
            Labels.add_label(self.arguments[0])
        elif self.op_code == "JUMP":
            self.check_arguments(Label)
            Labels.go_to_label(self.arguments[0])
        elif self.op_code == "JUMPIFEQ":
            self.check_arguments(Label, Symbol, Symbol)
            if isinstance(self.arguments[1], Variable):
                a = self.arguments[1].value()
            else:
                a = self.arguments[1]
            if isinstance(self.arguments[2], Variable):
                b = self.arguments[2].value()
            else:
                b = self.arguments[2]
            try:
                if a == b:
                    Labels.go_to_label(self.arguments[0])
            except:
                Error.exit(53, "JUMPIFEQ")
        elif self.op_code == "JUMPIFNEQ":
            self.check_arguments(Label, Symbol, Symbol)
            if isinstance(self.arguments[1], Variable):
                a = self.arguments[1].value()
            else:
                a = self.arguments[1]
            if isinstance(self.arguments[2], Variable):
                b = self.arguments[2].value()
            else:
                b = self.arguments[2]
            try:
                if a != b:
                    Labels.go_to_label(self.arguments[0])
            except:
                Error.exit(53, "JUMPIFEQ")
        elif self.op_code == "EXIT":
            self.check_arguments(Symbol)
            if isinstance(self.arguments[0], Variable):
                a = self.arguments[0].value()
            else:
                a = self.arguments[0]
            if not isinstance(a, int):
                Error.exit(57, "EXIT")
            if a < 0 or a > 49:
                Error.exit(57, "EXIT2")
            else:
                exit(a)
        elif self.op_code == "DPRINT":
            pass
        elif self.op_code == "BREAK":
            pass
        else:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "unknown instruction name")


input_file = None


def main():
    args = get_arguments()

    source_file = None
    global input_file
    if args["source"] is not False and args["input"] is not False:
        try:
            source_file = open(args["source"])
        except IOError:
            Error.exit(ERR_OPENING_INPUT_FILE, "cannot open source file")
        try:
            input_file = open(args["input"])
        except IOError:
            Error.exit(ERR_OPENING_INPUT_FILE, "cannot open input file")
    elif args["source"] is not False and args["input"] is False:
        try:
            source_file = open(args["source"])
        except IOError:
            Error.exit(ERR_OPENING_INPUT_FILE, "cannot open source file")
        input_file = sys.stdin
    elif args["source"] is False and args["input"] is not False:
        try:
            input_file = open(args["input"])
        except IOError:
            Error.exit(ERR_OPENING_INPUT_FILE, "cannot open source file")
        source_file = sys.stdin

    tree = None
    root = None

    try:
        tree = ET.parse(source_file)
    except Exception:
        Error.exit(ERR_WRONG_XML_FORMAT, "cannot parse xml file")
    try:
        root = tree.getroot()
        if root.tag != "program":
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "root elem program missing")
        if len(root.attrib) > 3:
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "root elem too many attribs")
            exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX)
        if len(root.attrib) == 3:
            if ("description" not in root.attrib) or ("name" not in root.attrib):
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "root 3 attribs wrong")
                exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX)
        if len(root.attrib) == 2:
            if ("description" not in root.attrib) and ("name" not in root.attrib):
                Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "root 2 attribs wrong")
        if root.attrib["language"] != "IPPcode19":
            Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "root attrib ippcode19 missing")
    except Exception:
        Error.exit(ERR_WRONG_XML_STRUCTURE_LEX_SYNTAX, "wrong root")

    Interpret.run(root)


if __name__ == '__main__':
    main()
