using System;

namespace RebirthTracker
{
    [AttributeUsage(AttributeTargets.Class)]
    public class OpcodeAttribute : Attribute
    {
        public byte Opcode { get; set; }

        /// <summary>
        /// Constructor
        /// </summary>
        public OpcodeAttribute(byte opcode)
        {
            Opcode = opcode;
        }
    }
}
